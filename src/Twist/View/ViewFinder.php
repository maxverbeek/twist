<?php namespace Twist\View;

use Closure;

class ViewFinder
{
	protected $path;

	protected $engines = [];

	protected $extensions = [];

	public function __construct($path)
	{
		$this->path = rtrim($path, '\\/') . '/';
	}

	public function find($view)
	{
		$paths = $this->getPossiblePaths($this->normalizeName($view));

		foreach ($paths as $extension => $path)
		{
			if (file_exists($path))
			{
				return [$this->views[$view] = $path, $extension];
			}
		}
	}

	protected function normalizeName($view)
	{
		return $this->path . str_replace('.', '/', $view);
	}

	protected function getPossiblePaths($path)
	{
		$result = [];

		foreach ($this->extensions as $extension)
		{
			$result[$extension] = $path . $extension;
		}

		return $result;
	}

	public function addExtension($extension)
	{
		$this->extensions[] = $extension;
	}
}