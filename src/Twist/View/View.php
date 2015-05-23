<?php namespace Twist\View;

class View
{
	protected $view;

	protected $data;

	protected $path;

	protected $engine;

	protected $manager;

	protected $environment;


	public function __construct($view, $data, $path, EngineInterface $engine, Manager $manager, Environment $environment)
	{
		$this->view = $view;
		$this->data = $data;
		$this->path = $path;
		$this->engine = $engine;
		$this->manager = $manager;
		$this->environment = $environment;
	}

	public function render()
	{
		$data = $this->data;
		$data['__env'] = $this->environment;

		$this->environment->incrementRender();

		$contents = $this->engine->get($this->path, $data);

		$this->environment->decrementRender();

		$this->environment->flushIfDone();

		return $contents;
	}

	public function make($view, $data)
	{
		return $this->manager->make($view, $data);
	}

	public function inject($name, $content)
	{
		return $this->environment->inject($name, $content);
	}

	public function yieldBlock($names, $default)
	{
		return $this->environment->yield($name, $default);
	}

	public function startBlock($name, $content)
	{
		return $this->environment->startBlock($name, $content);
	}

	public function endBlock()
	{
		return $this->environment->endBlock($name, $content);
	}
}