<?php namespace Twist\Slash;

use Twist\Slash\Exceptions\SlashSyntaxError;
use Twist\Slash\Exceptions\IndentationException;

class Slash
{
	/**
	 * A list of tasks that resembles the compilation
	 *
	 * @var array
	 */
	protected $tasks = [
		'parseLogicStatements',
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
	 * Current indentation level for the compiler
	 *
	 * @var integer
	 */
	protected $indentation = 0;

	protected $oldIndentation = 0;

	/**
	 * Stuff that gets inserted when outdenting
	 *
	 * @var array
	 */
	protected $closingTags = [];

	/**
	 * Default indentation pattern (1 tab or 4 spaces)
	 *
	 * @var string
	 */
	protected $indentPattern = '\t| {4}';

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
		$lines = preg_split("/[\r\n]+/", $content);

		$lines = $this->checkIndentationPattern($lines);

		foreach ($lines as $index => &$line)
		{
			$tags = $this->getExpiredClosingTags($line);

			if ($this->parsable($line))
			{
				$line = $this->parseLine($line, $index);
			}

			$line = $tags . $line;
		}

		$lines = $this->appendAllCloseTags($lines);

		return implode(PHP_EOL, $lines);
	}

	public function checkIndentationPattern($lines)
	{
		list($open, $close) = array_map('preg_quote', $this->logic);

		if (preg_match("/^{$open}[\t ]*indent[\t ]*\'([^\']+)\'[\t ]*{$close}/", $lines[0], $matches))
		{
			$this->indentPattern = $matches[1];
			unset($lines[0]);
		}

		return $lines;
	}

	protected function appendAllCloseTags($lines)
	{
		while ($this->indentation > 0)
		{
			$lines[] = $this->getClosingTag($this->indentation--);
		}

		return $lines;
	}

	protected function getExpiredClosingTags($line)
	{
		$previous = $this->indentation;
		$this->indentation = $this->getIndentation($line);

		$tags = '';

		while ($this->indentation < $previous)
		{
			$tags .= $this->getClosingTag($previous--);
		}

		return $tags;
	}

	/**
	 * Compile the line of slash into valid PHP
	 *
	 * @param  string $line Slash
	 *
	 * @return string       PHP
	 */
	protected function parseLine($line, $index)
	{
		$this->currentLineNumber = $index + 1;
		$this->currentLine = $line;

		foreach ($this->tasks as $task)
		{
			$line = $this->{$task}($line);
		}

		return $line;
	}

	protected function getClosingTag($level)
	{
		if (isset($this->closingTags[$level]))
		{
			$tag = $this->closingTags[$level];

			unset($this->closingTags[$level]);

			return $tag;
		}

		return '';
	}

	protected function newTag($tag)
	{
		$this->closingTags[$this->indentation] = $tag;
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

	protected function parseLogicStatements($line)
	{
		list($open, $close) = array_map('preg_quote', $this->logic);

		$callback = function ($matches)
		{
			if (preg_match('/^(?:where|for|while|if|else|elseif)/', $matches[1], $clause))

			return $this->{'create'.ucfirst($clause[0]).'Logic'}($matches[1]);

			else

			throw new SlashSyntaxError("invalid statement \"{$matches[1]}\"", $this->currentLineNumber, $this->currentLine);
		};

		return preg_replace_callback("/{$open}[\t ]*(.+?)[\t ]*{$close}/", $callback, $line);
	}

	protected function createForLogic($statement)
	{
		// From PHP wiki
		$var = '\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*';

		$original = $statement;

		$statement = preg_replace("/^for ({$var}) in (\d+)$/i", '<?php for($1 = 1; $1 <= $2; $1++): ?>', $statement);

		if ($original !== $statement)
		{
			$this->newTag('<?php endfor; ?>');

			return $statement;
		}

		$statement = preg_replace("/^for ({$var}) in ({$var})$/i", '<?php foreach ($2 as $1): ?>', $statement);

		$statement = preg_replace("/^for ({$var})[\t ]*:[\t ]*({$var}) in ({$var})$/i", '<?php foreach ($3 as $1 => $2): ?>', $statement);

		if ($original !== $statement)
		{
			$this->newTag('<?php endforeach; ?>');

			return $statement;
		}

		throw new SlashSyntaxError("Invalid for loop", $this->currentLineNumber, $this->currentLine);
	}

	protected function parseComments($line)
	{
		list($open, $close) = array_map('preg_quote', $this->comment);
	}

	/**
	 * Determines wheter a line should be parsed or not.
	 *
	 * @param  string $line
	 *
	 * @return bool
	 */
	protected function parsable($line)
	{
		return preg_match_all('/\{[{$%]{1,2}/', $line) > 0;
	}

	protected function indentPattern()
	{
		return "/" . $this->indentPattern . "/";
	}

	protected function getIndentation($line)
	{
		if (preg_match('/^\s+/', $line, $ws))
		{
			return preg_match_all($this->indentPattern(), $ws[0]);
		}

		return 0;
	}
}