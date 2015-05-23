<?php namespace Twist\Slash;

class SlashStandalone
{
	protected $compiler;

	protected $path;

	protected $compiled;

	protected $blockStack = [];

	protected $blocks = [];

	protected $render = 0;

	public function __construct(SlashCompiler $compiler, $path, $compiled)
	{
		$this->compiler = $compiler;
		$this->path = $path;
		$this->compiled = $compiled;
	}

	public function make($view, $data = array())
	{
		$this->incrementRender();

		$file = $this->normalizeFile($view);

		if ($exists = ! file_exists($file))
		{
			throw new \InvalidArgumentException("File [$view] could not be found at $file");
		}

		$cache = $this->getCachePath($file);

		if (! file_exists($cache) || filemtime($file) > filemtime($cache))
		{
			$this->recompile($file, $cache);
		}

		$data = $this->formatData($data);

		$content = new Renderable($this->evaluate($cache, $data));

		$this->decrementRender();

		$this->flushIfDone();

		return $content;
	}

	protected function normalizeFile($file)
	{
		return rtrim($this->path, '\\/') . '/' . str_replace('.', '/', $file) . SlashCompiler::EXTENSION;
	}

	protected function getCachePath($file)
	{
		return rtrim($this->compiled, '\\/') . '/' . md5($file);
	}

	protected function recompile($source, $destination)
	{
		$s = fopen($source, 'r');
		$d = fopen($destination, 'w');

		fwrite($d, $this->compiler->compile(fread($s, filesize($source) + 1)));

		fclose($s);
		fclose($d);
	}

	protected function formatData($data)
	{
		if (isset($data['__path'])) unset($data['__path']);
		if (isset($data['__data'])) unset($data['__data']);
		$data['__env'] = $this;

		return $data;
	}

	protected function evaluate($__path, $__data)
	{
		extract($__data);

		$oblevel = ob_get_level();

		ob_start();

		try
		{
			require $__path;
		}

		catch(Exception $e)
		{
			while (ob_get_level() > $oblevel)
			{
				ob_end_clean();
			}
		}

		return ob_get_clean();
	}

	public function incrementRender()
	{
		$this->render++;
	}

	public function decrementRender()
	{
		$this->render--;
	}

	public function flush()
	{
		$this->blocks = [];
		$this->blockStack = [];
	}

	public function flushIfDone()
	{
		if ($this->render === 0)
		{
			$this->flush();
		}
	}

	public function yieldBlock($name, $default)
	{
		return isset($this->blocks[$name]) ? $this->blocks[$name] : $default;
	}

	public function inject($name, $content)
	{
		if (isset($this->blocks[$name]))
		{
			$content = preg_replace($this->compiler->getParentTag(), $this->blocks[$name], $content);
		}

		$this->blocks[$name] = $content;
	}

	public function startBlock($name, $content = '')
	{
		if ($content === '')
		{
			if (ob_start()) $this->blockStack[] = $name;
		}

		else
		{
			$this->inject($name, $content);
		}
	}

	public function endBlock()
	{
		$last = array_pop($this->blockStack);

		$this->inject($last, ob_get_clean());
	}
}