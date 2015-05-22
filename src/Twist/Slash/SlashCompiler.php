<?php namespace Twist\Slash;

use Twist\Slash\Exceptions\SlashSyntaxError;
use Twist\Slash\Exceptions\IndentationException;
use Closure;

class SlashCompiler
{
	/**
	 * Holds the extension of the slash files.
	 *
	 * @constant string
	 */
	const EXTENSION = '.slash';

	/**
	 * A list of functions that compile a template globally
	 *
	 * @var  array
	 */
	protected $globalTasks = [
		'parseComments',
	];

	/**
	 * A list of functions that compile a line
	 *
	 * @var array
	 */
	protected $lineTasks = [
		'parseLogicStatements',
		'parseFunctionStatements',
		'parseEmptyStatements',
		'parseEndStatements',
		'parseEchoes',
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
	public function compile($slash)
	{
		$this->indentation = 0;
		$this->indentCallbacks = [];

		$result = '';

		foreach (token_get_all($slash) as &$token)
		{
			$result .= is_array($token) ? $this->parseToken($token) : $token;
		}

		return ltrim($result, PHP_EOL."\t");
	}

	/**
	 * Compile a token returned by the PHP Zend Lexical scanner
	 *
	 * @param  array $token
	 *
	 * @return string
	 */
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

	/**
	 * Compile a string of text to valid PHP
	 *
	 * @param  string $content
	 *
	 * @return string
	 */
	protected function parse($content)
	{
		$content = $this->parseGlobal($content);

		$lines = preg_split('/[\r\n]/', $content);

		$this->parseExtends($lines);

		return implode(PHP_EOL, $this->parseLines($lines));
	}

	/**
	 * Compile the first line, and check if the template should be extended
	 *
	 * @param  array &$lines
	 *
	 * @return array
	 */
	protected function parseExtends(&$lines)
	{
		list($open, $close) = array_map('preg_quote', $this->logic);

		$ws = '[\\t ]*';

		$pattern = "/^{$open}{$ws}extends ((['\"])(.*?)\\2){$ws}{$close}/";

		if (preg_match($pattern, $lines[0], $match))
		{
			// If there is an "extends" tag, remove the first line, and
			// include the extended template at the bottom of the page.
			// this gives the dev a chance to define stuff here, and have
			// it executed in the file below.
			array_shift($lines);
			$file = $match[1];
			$lines[] = "<?php echo \$__env->make({$file}, get_defined_vars()); ?>";
		}
	}

	/**
	 * Parse a whole string at once
	 *
	 * @param  string $content
	 *
	 * @return string
	 */
	protected function parseGlobal($content)
	{
		if (! $this->parsable($content)) return $content;

		foreach ($this->globalTasks as $task)
		{
			$content = $this->{$task}($content);
		}

		return $content;
	}

	/**
	 * Parse a string line by line
	 *
	 * @param  string $content
	 *
	 * @return string
	 */
	protected function parseLines($lines)
	{
		foreach ($lines as &$line)
		{
			if ($this->parsable($line))
			{
				foreach ($this->lineTasks as $task)
				{
					$line = $this->{$task}($line);
				}
			}
		}

		return $lines;
	}

	/**
	 * Append an ending tag to the end-tag array
	 *
	 * @param  Closure|string $ending
	 *
	 * @return void
	 */
	protected function pushEnding($ending)
	{
		$this->endings[] = $ending;
	}

	/**
	 * Pop the last ending of the end-tag array and return it
	 *
	 * @return string
	 */
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

	/**
	 * Handles for, while, if's and spaceless tags and sends them to
	 * their respective methods
	 *
	 * @param  string $line
	 *
	 * @return string
	 */
	protected function parseLogicStatements($line)
	{
		list($open, $close) = array_map('preg_quote', $this->logic);

		$callback = function ($matches)
		{
			return $this->{'compile'.ucfirst($matches[2]).'Statements'}($matches[1]);
		};

		return preg_replace_callback("/{$open}[\\t ]*((for|while|if|elseif|else|spaceless)[\\t ]+.*?)[\\t ]*{$close}/", $callback, $line);
	}

	/**
	 * Select a for loop, and decide whether it is a for or a foreach loop
	 *
	 * @param  string $statement
	 *
	 * @return string
	 */
	protected function compileForStatements($statement)
	{
		// PHP wiki (language.variables.basic)
		$var = "\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*";

		if (preg_match("/for ({$var}) in (\d+)/", $statement, $match))
		{
			return $this->compileFor($statement, $match[1], $match[2]);
		}

		elseif (preg_match($pattern = "/for ({$var})(?:[\\t ]*:[\\t ]*({$var}))? in (.*)/", $statement, $match))
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

	/**
	 * Compile a for loop to native PHP
	 *
	 * @param  string $statement
	 * @param  string $var
	 * @param  string $to
	 *
	 * @return string
	 */
	protected function compileFor($statement, $var, $to)
	{
		$this->pushEnding('<?php endfor; ?>');

		return "<?php for ({$var} = 1; {$var} <= {$to}; {$var}++): ?>";
	}

	/**
	 * Compile a foreach loop to native PHP
	 *
	 * @param  string $statement
	 * @param  array $matches
	 *
	 * @return string
	 */
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

	/**
	 * Compile a while loop to native PHP
	 *
	 * @param  string $statement
	 *
	 * @return string
	 */
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

	/**
	 * Compile an if statement to native PHP
	 *
	 * @param  string $statement
	 *
	 * @return string
	 */
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

	/**
	 * Compile an elseif statement to native PHP
	 *
	 * @param  string $statement
	 *
	 * @return string
	 */
	protected function compileElseifStatements($statement)
	{
		return preg_replace("/elseif (.*)/", "<?php elseif (\\1): ?>", $statement);
	}

	/**
	 * Compile an else statement to native PHP
	 *
	 * @param  string $statement
	 *
	 * @return string
	 */
	protected function compileElseStatements($statement)
	{
		return '<?php else: ?>';
	}

	/**
	 * Compile a spaceless tag to native PHP
	 *
	 * @param  string $statement
	 *
	 * @return string
	 */
	protected function compileSpacelessStatements($statement)
	{
		$ob_end = "<?php echo trim(preg_replace('/>\s+</', '><', ob_get_clean())); ?>";
		$this->pushEnding($ob_end);

		return '<?php ob_start(); ?>';
	}

	/**
	 * Compiles yield, block and include statements using their respective methods
	 *
	 * @param  string $line
	 *
	 * @return string
	 */
	protected function parseFunctionStatements($line)
	{
		$callback = function ($match)
		{
			$function = ucfirst($match[1]);
			$name     = $match[2] . $match[3] . $match[2];
			$default  = isset($match[5]) ? $match[4] . $match[5] . $match[4] : '';

			return $this->{"compile{$function}Statements"}($name, $default);
		};

		list($open, $close) = array_map('preg_quote', $this->logic);

		$ws = '[\\t ]*';
		$functions = 'yield|block|include';

		$pattern = "/{$open}{$ws}({$functions}){$ws}(['\"])(.*?)\\2{$ws}(?:,{$ws}(['\"])(.*?)\\4)?{$ws}{$close}/";

		return preg_replace_callback($pattern, $callback, $line);
	}

	/**
	 * Compiles all of the yield statements down to yieldBlock calls
	 *
	 * @param  string $name
	 * @param  string $default
	 *
	 * @return string
	 */
	protected function compileYieldStatements($name, $default = '')
	{
		if (! $default) $default = "''";
		return "<?php echo \$__env->yieldBlock({$name}, {$default}); ?>";
	}

	/**
	 * Compile all of the block statements down to startBlock calls
	 *
	 * @param  string $name
	 * @param  string $content
	 *
	 * @return string
	 */
	protected function compileBlockStatements($name, $content = '')
	{
		if (! $content)
		{
			$content = "''";
			$ending = "<?php \$__env->endBlock(); ?>";
			$this->pushEnding($ending);

			return "<?php \$__env->startBlock({$name}); ?>";
		}

		return "<?php \$__env->inject({$name}, {$content}); ?>";
	}

	/**
	 * Compile Include statements to calls to the make method.
	 *
	 * @param  string $name
	 *
	 * @return string
	 */
	protected function compileIncludeStatements($name)
	{
		return "<?php echo \$__env->make({$name}, get_defined_vars()); ?>";
	}

	/**
	 * Compile empty statements for loops to native PHP if-statements
	 *
	 * @param  string $line
	 *
	 * @return string
	 */
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

		$ws = '[\\t ]*';

		$pattern = "/{$open}{$ws}empty{$ws}{$close}/";

		return preg_replace_callback($pattern, $callback, $line);
	}

	/**
	 * Handles all of the end tags, and
	 * applies all of the listeners on them
	 *
	 * @param  string $line
	 *
	 * @return string
	 */
	protected function parseEndStatements($line)
	{
		$callback = function ($match)
		{
			return $this->popEnding();
		};

		list($open, $close) = array_map('preg_quote', $this->logic);

		$ws = '[\\t ]*';

		$pattern = "/{$open}{$ws}end{$ws}{$close}/";

		return preg_replace_callback($pattern, $callback, $line);
	}

	/**
	 * Compile all of the slash echoes on a line to
	 * native PHP escaped echoes using htmlentities
	 *
	 * @param  string $line
	 *
	 * @return string
	 */
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

	/**
	 * Compile comments to native PHP comments
	 *
	 * @param  string $content
	 *
	 * @return string
	 */
	protected function parseComments($content)
	{
		list($open, $close) = array_map('preg_quote', $this->comment);

		$pattern = "/{$open}(.*?){$close}/s";

		return preg_replace($pattern, "<?php /* \\1 */ ?>", $content);
	}

	/**
	 * Determine if a line should be parsed or not
	 *
	 * @param  string $line
	 *
	 * @return bool
	 */
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
	 * Make a pattern to match a parent tag
	 *
	 * @return string
	 */
	public function getParentPattern()
	{
		list($open, $close) = array_map('preg_quote', $this->logic);

		return "/{$open}[\\t ]*parent[\\t ]*{$close}/";
	}

	/**
	 * If there are many for loops on page
	 * The index will become negative. Since a '-' is
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