<?php namespace Twist\View;

use Closure;

class ViewResolver
{
	protected $path;

	protected $engines = [];

	protected $extensions = [];

	public function __construct($path)
	{
		$this->path = rtrim($path, '\\/') . '/';
	}

	public function setManager(Manager $manager)
	{
		$this->manager = $manager;
	}

	public function getEnvironment()
	{
		return $this->manager->getEnvironment();
	}

	public function resolve($view, $data)
	{
		$paths = $this->getPossiblePaths($this->normalizeName($view));

		foreach ($paths as $extension => $path)
		{
			if (file_exists($path))
			{
				return new View($view, $data, $path, $this->getEngine($extension), $this->manager, $this->getEnvironment());
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

	protected function getEngine($extension)
	{
		$engine = $this->engines[$extension];

		if ($engine instanceof Closure)
		{
			return $engine();
		}

		return $engine;
	}

	public function register($extension, $engine)
	{
		$this->extensions[] = $extension;
		$this->engines[$extension] = $engine;
	}
}