<?php namespace Twist\View;

use Closure;

class Manager
{
	protected $finder;

	protected $path = '';

	protected $engines = [];

	public function __construct(ViewFinder $finder)
	{
		$this->finder = $finder;
	}

	public function make($viewname, $data = [])
	{
		$view = $this->finder->find($this->path . $viewname);

		if (! $view)
		{
			throw new ViewNotFoundException("View [$viewname] could not be found.");
		}

		list($path, $extension) = $view;

		$engine = $this->getEngine($extension);

		return new View($viewname, $data, $path, $extension, $this);
	}

	public function getEvaluated($viewname, $environment, $defined)
	{
		$view = $this->finder->find($viewname);

		if (! $view)
		{
			throw new ViewNotFoundException("View [$viewname] could not be found.");
		}

		list($path, $extension) = $view;

		$engine = $this->getEngine($extension);

		$defined['__env'] = $environment;

		return $engine->get($path, $defined);
	}

	public function getEnvironment()
	{
		return $this->environment;
	}

	public function getEngine($extension)
	{
		if (! isset($this->engines[$extension]))
		{
			throw new RuntimeException("Engine for extension [$extension] not found.");
		}

		$engine = $this->engines[$extension];

		if ($engine instanceof Closure)
		{
			return $this->engines[$extension] = $engine($this);
		}

		return $engine;
	}

	public function register($extension, $engine)
	{
		$this->engines[$this->extensions[] = $extension] = $engine;
		$this->finder->addExtension($extension);
	}
}