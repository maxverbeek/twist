<?php namespace Twist\View;

use Closure;

class ViewResolver
{
	/**
	 * A list of possible extensions
	 *
	 * @var array
	 */
	protected $extensions = [];

	/**
	 * A list to map an extension to an array
	 *
	 * @var [type]
	 */
	protected $engines = [];

	/**
	 * Known existing views
	 *
	 * @var array
	 *
	 */
	protected $views = [];

	public function __construct($paths)
	{
		$this->extensions = $extensions;
		$this->paths = $paths;
	}

	public function find($view)
	{
		$view = str_replace('.', '/', $view);

		// If we've already found the view, don't look for
		// it again.
		if (isset($this->views[$view]))
		{
			return $this->views[$view];
		}

		foreach ($this->getPossibleViewNames($view) as $name)
		{
			foreach ((array) $this->paths as $path)
			{
				if (file_exists($path . $name))
				{
					return $this->views[$view] = $path . $name;
				}
			}
		}

		return false;
	}

	/**
	 * Appends every extension to a view to get the possible file name.
	 *
	 * @param  string $view
	 *
	 * @return array
	 */
	protected function getPossibleViewNames($view)
	{
		return array_map(function ($extension) use ($view)
		{
			return $view . $extension;
		}, $this->extensions);
	}

	/**
	 * Map an extension to an engine
	 *
	 * @param  string $extension
	 * @param  \Twist\View\EngineInterface|Closure $engine
	 *
	 * @return void
	 */
	public function register($extension, $engine)
	{
		$this->extensions = $extension;
		$this->engines[$extension] = $engine;
	}

	/**
	 * Get an instance of the engine needed for the view
	 *
	 * @param  string $view
	 *
	 * @return \Twist\View\EngineInterface
	 */
	public function getEngine($view)
	{
		if ($path = $this->find($view))
		{
			$extension = substr($view, 0, strlen($view));

			if (isset($this->engines[$extension]))
			{
				$engine = $this->engines[$extension];

				if ($engine instanceof Closure)
				{
					$engine = $engine();
				}

				return $engine;
			}
		}
	}
}