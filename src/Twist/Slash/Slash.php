<?php namespace Twist\Slash;

use Twist\Slash\Exceptions\SlashSyntaxError;
use Twist\Slash\Exceptions\IndentationException;
use Closure;

class Slash
{
	/**
	 * A list of tasks that resembles the compilation
	 *
	 * @var array
	 */
	protected $tasks = [
		'parseLogicStatements',
		'parseEmptyStatements',
		'parseEndStatements',
		'parseEchoes',
		'parseComments',
	];

	/**
	 * Tags being used by compiler
	 *
	 * @var  array
	 * @var  array
	 * @var  array
	 */
	protected $echo = ['{{', '}}'];
	protected $logic = ['{%', '%}'];
	protected $comment = ['{#', '#}'];

	/**
	 * Stuff that replaces the end tag
	 *
	 * @var array
	 */
	protected $endings = [];

	/**
	 * Counter to give foreach loops an ID (used for empty statements)
	 *
	 * @var integer
	 */
	protected $foreachCount = 0;

	/**
	 * Turns slash code into valid PHP
	 *
	 * @param  string $slash Raw slash code
	 *
	 * @return string        Valid PHP
	 */
	public function make($slash)
	{
		$this->indentation = 0;
		$this->indentCallbacks = [];

		$result = '';

		foreach (token_get_all($slash) as &$token)
		{
			$result .= is_array($token) ? $this->parseToken($token) : $token;
		}

		return $result;
	}

	protected function parseToken($token)
	{
		list($id, $content) = $token;

		// Fetch all of the non-php code
		if ($id == T_INLINE_HTML)
		{
			$content = $this->parse($content);
		}

		return $content;
	}

	protected function parse($content)
	{
		$lines = preg_split('/[\r\n]+/', $content);

		foreach ($lines as &$line)
		{
			foreach ($this->tasks as $task)
			{
				if ($this->parsable($line))
				{
					$line = $this->{$task}($line);
				}
			}
		}

		return implode(PHP_EOL, $lines);
	}

	protected function pushEnding($ending)
	{
		$this->endings[] = $ending;
	}

	protected function popEnding()
	{
		$ending = array_pop($this->endings);

		if ($ending instanceof Closure)
		{
			// If the ending is a closure, we'll execute it.
			// this allows for extra logic being performed
			// right before the ending is inserted.
			$ending = $ending();
		}

		return $ending;
	}

	protected function parseLogicStatements($line)
	{
		list($open, $close) = array_map('preg_quote', $this->logic);

		$callback = function ($matches)
		{
			return $this->{'compile'.ucfirst($matches[2]).'Statements'}($matches[1]);
		};

		return preg_replace_callback("/{$open}[\t ]*((for|while|if|elseif|else)[\t ]+.*?)[\t ]*{$close}/", $callback, $line);
	}

	protected function compileForStatements($statement)
	{
		// PHP wiki (language.variables.basic)
		$var = "\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*";

		if (preg_match("/for ({$var}) in (\d+)/", $statement, $match))
		{
			return $this->compileFor($statement, $match[1], $match[2]);
		}

		elseif (preg_match($pattern = "/for ({$var})(?:[\t ]*:[\t ]*({$var}))? in (.*)/", $statement, $match))
		{
			return $this->compileForeach($statement, $match);
		}

		else
		{
			echo "Iets is pittig verneukt en het is niet jurryts moeder";
			echo $statement . '  ' . $pattern;
			echo PHP_EOL;
		}
	}

	protected function compileFor($statement, $var, $to)
	{
		$this->pushEnding('<?php endfor; ?>');

		return "<?php for ({$var} = 1; {$var} <= {$to}; {$var}++): ?>";
	}

	protected function compileForeach($statement, $matches)
	{
		if (isset($matches[3])) list(, $key, $value, $array) = $matches;

		else list(, $value, $array) = $matches;

		$empty = $this->createEmptyVariable($this->foreachCount++);

		$this->pushEnding(function () {
			$this->foreachCount--;
			return '<?php endforeach; ?>';
		});

		$iterated = isset($key) ? "{$key} => {$value}" : $value;

		return "<?php {$empty} = true; foreach ({$array} as {$iterated}): {$empty} = false; ?>";
	}

	protected function compileIfStatements($statement)
	{
		$callback = function ($match)
		{
			$this->pushEnding('<?php endif; ?>');

			return "<?php if ({$match[1]}): ?>";
		};

		$pattern = "/if (.*)/";

		return preg_replace_callback($pattern, $callback, $statement);
	}

	protected function compileElseifStatements($statement)
	{
		return preg_replace("/elseif (.*)/", "<?php elseif (\\1): ?>", $statement);
	}

	protected function compileElseStatements($statement)
	{
		return '<?php else: ?>';
	}

	protected function compileWhileStatements($statement)
	{
		$callback = function ($match)
		{
			$this->pushEnding('<?php endwhile; ?>');

			return "<?php while ({$match[1]}): ?>";
		};

		$pattern = "/while (.*)/";

		return preg_replace_callback($pattern, $callback, $statement);
	}

	protected function parseEmptyStatements($line)
	{
		$callback = function ($match)
		{
			$empty = $this->createEmptyVariable(--$this->foreachCount);

			$this->popEnding();
			$this->pushEnding('<?php endif; ?>');

			return "<?php endforeach; if ({$empty}): ?>";
		};

		list($open, $close) = array_map('preg_quote', $this->logic);

		$ws = '[\t ]*';

		$pattern = "/{$open}{$ws}empty{$ws}{$close}/";

		return preg_replace_callback($pattern, $callback, $line);
	}

	protected function parseEndStatements($line)
	{
		$callback = function ($match)
		{
			return $this->popEnding();
		};

		list($open, $close) = array_map('preg_quote', $this->logic);

		$ws = '[\t ]*';

		$pattern = "/{$open}{$ws}end{$ws}{$close}/";

		return preg_replace_callback($pattern, $callback, $line);
	}

	protected function parseEchoes($line)
	{
		list($open, $close) = array_map('preg_quote', $this->echo);

		$callback = function ($matches)
		{
			$value = preg_replace('/^(?=\$)(.+?)(?:\s+or\s+)(.+?)$/', 'isset($1) ? $1 : $2', $matches[1]);

			return "<?php echo htmlentities({$value}, ENT_QUOTES, \"UTF-8\"); ?>";
		};

		return preg_replace_callback("/{$open}[\t ]*(.+?)[\t ]*{$close}/", $callback, $line);
	}

	protected function parseComments($line)
	{
		list($open, $close) = array_map('preg_quote', $this->comment);

		$pattern = "/{$open}(.*?){$close}/";

		return preg_replace($pattern, "<?php /* \\1 */ ?>", $line);
	}

	protected function parsable($line)
	{
		$tags = [];

		foreach ([$this->logic, $this->echo, $this->comment] as $tag)
		{
			$tags[] = preg_quote($tag[0], '/') . '|' . preg_quote($tag[1], '/');
		}

		$tags = implode('|', $tags);

		return preg_match("/{$tags}/", $line, $match) === 1;
	}

	/**
	 * If there are many for loops on page
	 * The index will become negative, since a - is
	 * not a valid variable character, we replace that here.
	 *
	 * @param  integer $index
	 *
	 * @return string
	 */
	protected function createEmptyVariable($index)
	{
		$empty = '$__empty_' . $index;

		return str_replace('-', '_', $empty);
	}
}