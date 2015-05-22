<?php namespace Twist\Slash;

use Twist\View\PhpEngine;
use Twist\View\EngineInterface;
use Twist\View\ManagerInterface;

class Slash extends PhpEngine implements EngineInterface
{
	/**
	 * The Slash compiler
	 *
	 * @var \Twist\Slash\Compiler
	 */
	protected $compiler;

	/**
	 * A manager that hosts the templating engine
	 *
	 * @var \Twist\View\ManagerInterface
	 */
	protected $manager;

	/**
	 * The list of blocks currently being used
	 *
	 * @var array
	 */
	protected $blocks = [];

	/**
	 * The list of blocks that are currently being made.
	 *
	 * @var array
	 */
	protected $blockStack = [];

	public function __construct(SlashCompiler $compiler, $cache)
	{
		$this->compiler = $compiler;
		$this->cache = $cache;
	}

	/**
	 * Get the evaluated contents of a slash file
	 *
	 * @param  string $path
	 * @param  array  $data
	 *
	 * @return string
	 */
	public function get($path, array $data = array())
	{
		// Unset some predefined variables, since we may
		// be processing an included partial. If we don't
		// do this, __path overwrites the current $__path.
		if (isset($data['__data'])) unset($data['__data']);
		if (isset($data['__path'])) unset($data['__path']);

		$cachefile = $this->getCachePath($path);

		// If the view is modified after the
		// last compilation it is expired, and must
		// be recompiled
		$exists = file_exists($cachefile);

		if (! $exists || $exists && filemtime($path) > filemtime($cachefile))
		{
			$cache = fopen($cachefile, 'w');
			$view = fopen($path, 'r');
			fwrite($cache, $this->compiler->compile(fread($view, filesize($path))));

			fclose($cache);
			fclose($view);
		}

		$data['__env'] = $this;

		return parent::get($cachefile, $data);
	}

	/**
	 * Get the location of the cache file
	 *
	 * @param  string $file
	 *
	 * @return string
	 */
	protected function getCachePath($file)
	{
		return $this->cache . '/' . md5($file);
	}

	/**
	 * Set a manager for supporting other view engines
	 *
	 * @param \Twist\View\ManagerInterface $manager
	 */
	public function setManager(ManagerInterface $manager)
	{
		$this->manager = $manager;
	}

	/**
	 * Retreive another file. Use a different environment if given.
	 *
	 * @param  string $file
	 * @param  array  $data
	 *
	 * @return string|\Twist\View\ViewInterface
	 */
	public function make($file, $data = array())
	{
		if ($this->manager)
		{
			return $this->manager->make($file, $data);
		}

		// Since all of Twist's views are provided without an extension
		// we'll have to add our extension here, since slash is the only
		// language that is supported if no other manager is given.
		$file = str_replace('.', '/', $file) . SlashCompiler::EXTENSION;

		return $this->get($file, $data);
	}

	/**
	 * Begin a new block (compiler output)
	 *
	 * @param  string $name
	 * @param  string $content
	 *
	 * @return void
	 */
	public function startBlock($name, $content = '')
	{
		if ($content === '')
		{
			if (ob_start())	$this->blockStack[] = $name;
		}

		else
		{
			$this->inject($name, $content);
		}
	}

	public function inject($name, $content = '')
	{
		if (isset($this->blocks[$name]))
		{
			$content = preg_replace(
				$this->compiler->getParentPattern(),
				$this->blocks[$name],
				$content
			);
		}

		$this->blocks[$name] = $content;
	}

	/**
	 * End the block (compiler output)
	 *
	 * @return string
	 */
	public function endBlock()
	{
		$last = array_pop($this->blockStack);

		if (isset($this->blocks[$last]))
		{
			$this->blocks[$last] = preg_replace(
				$this->compiler->getParentPattern(),
				$this->blocks[$last],
				ob_get_clean()
			);
		}

		else
		{
			$this->blocks[$last] = ob_get_clean();
		}
	}

	/**
	 * Retreive a block
	 *
	 * @param  string $block
	 * @param  string $default
	 *
	 * @return string
	 */
	public function yieldBlock($block, $default = '')
	{
		return isset($this->blocks[$block]) ? $this->blocks[$block] : $default;
	}
}