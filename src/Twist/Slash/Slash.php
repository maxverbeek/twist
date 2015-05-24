<?php namespace Twist\Slash;

use Twist\View\PhpEngine;

class Slash extends PhpEngine
{
	public function __construct(SlashCompiler $compiler, $cachepath, $forcecompile = false)
	{
		$this->compiler = $compiler;
		$this->cachepath = rtrim($cachepath, '\\/') . '/';
		$this->forcecompile = $forcecompile;
	}

	public function get($path, array $data = array())
	{
		$cache = $this->getCachePath($path);

		if ($this->forcecompile || (! file_exists($cache) || $this->cacheOutdated($path, $cache)))
		{
			$this->recompile($path, $cache);
		}

		return parent::get($cache, $data);
	}

	protected function cacheOutdated($view, $cache)
	{
		return filemtime($view) > filemtime($cache);
	}

	protected function getCachePath($path)
	{
		return $this->cachepath . md5($path);
	}

	protected function recompile($path, $cache)
	{
		$p = fopen($path, 'r');
		$c = fopen($cache, 'w');

		fwrite($c, $this->compiler->compile(fread($p, filesize($path) + 1)));

		fclose($p);
		fclose($c);
	}
}