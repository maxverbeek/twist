<?php namespace Twist\View;

class Manager
{
	/**
	 * ViewResolver, used to locate the views
	 *
	 * @var \Twist\View\ViewResolver
	 */
	protected $resolver;

	public function __construct(ViewResolver $resolver)
	{
		$this->resolver = $resolver;
	}

	/**
	 * Return the contents of a view
	 *
	 * @return string
	 */
	public function make($view, $data = array())
	{
		$file = $this->resolver->find($file);

		$engine = $this->resolver->getEngine($view);
	}
}