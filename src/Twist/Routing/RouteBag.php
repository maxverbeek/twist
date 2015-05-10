<?php namespace Twist\Routing;

class RouteBag
{
	protected $routes = array();

	protected $namedRoutes = array();

	protected $methodRoutes = array();

	public function add(Route $route)
	{
		$this->addRoute($route);
		$this->addNamedRoute($route);
		$this->addMethodRoute($route);
	}

	protected function addRoute($route)
	{
		$this->routes[] = $route;
	}

	protected function addNamedRoute($route)
	{
		if (! empty($route->name))
		{
			$this->namedRoutes[$route->name] = $route;
		}
	}

	protected function addMethodRoute($route)
	{
		$this->methodRoutes[$route->method][] = $route;
	}
}