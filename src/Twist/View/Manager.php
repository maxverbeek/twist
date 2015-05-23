<?php namespace Twist\View;

class Manager
{
	protected $environment;

	protected $resolver;

	protected $path = '';

	public function __construct(ViewResolver $resolver, Environment $environment)
	{
		$this->resolver = $resolver;
		$this->resolver->setManager($this);
		$this->environment = $environment;
		$this->environment->setManager($this);
	}

	public function make($viewname, $data = [])
	{
		$view = $this->resolver->resolve($this->path . $viewname, $data);

		if (! $view)
		{
			throw new ViewNotFoundException("View [$viewname] could not be found.");
		}

		return $view;
	}

	public function getEnvironment()
	{
		return $this->environment;
	}
}