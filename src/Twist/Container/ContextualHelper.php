<?php namespace Twist\Container;

class ContextualHelper
{
	/**
	 * Classes to apply this binding to
	 *
	 * @var array
	 */
	protected $when = [];

	/**
	 * List of subjects that will be replaced
	 *
	 * @var array
	 */
	protected $needs = [];

	/**
	 * The replacement for the 'need' clause
	 *
	 * @var mixed
	 */
	protected $give;

	public function __construct(Container $container)
	{
		$this->container = $container;
	}

	/**
	 * Provide 1 or more subjects
	 *
	 * @param  dynamic $when One or more classes that will have a replacement
	 *
	 * @return $this
	 */
	public function when($when)
	{
		$when = is_array($when) ? $when : func_get_args();

		$this->when = array_merge($this->when, $when);

		return $this;
	}

	/**
	 * Provide one or more triggers for a replacement
	 *
	 * @param  dynamic $needs One or more classes that trigger a replacement
	 *
	 * @return $this
	 */
	public function needs($needs)
	{
		$needs = is_array($needs) ? $needs : func_get_args();

		$this->needs = array_merge($this->needs, $needs);

		return $this;
	}

	/**
	 * Provide a replacement, and bind in the container
	 *
	 * @param  mixed $give
	 *
	 * @return void
	 */
	public function give($give)
	{
		$this->container->addContextual($this->when, $this->needs, $give);
	}
}