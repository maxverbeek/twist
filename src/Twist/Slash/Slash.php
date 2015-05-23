<?php namespace Twist\Slash;

use Twist\View\PhpEngine;

class Slash extends PhpEngine
{
	public function __construct(SlashCompiler $compiler, $cachepath, $cache = true)
	{
		$this->compiler = $compiler;
		$this->cachepath = rtrim($cachepath, '\\/') . '/';
		$this->cache = $cache;
	}

	public function get($path, array $data = array())
	{
		$cache = $this->getCachePath($path);

		if (! $this->cache || (! file_exists($cache) || $this->cacheOUtdated($path, $cache)))
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
		echo $path;
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