<?php namespace Twist\Routing;

class Router
{
	protected $currentRoute = array();

	protected $routes;

	protected $patterns = array(
		':any' => '[^/]+',
		':num' => '[0-9]+',
		':alnum' => '[0-9a-zA-Z]+'
	);

	protected $defaultPattern = '[^/]+';

	public function __construct()
	{
		$this->routes = new RouteBag;
	}

	public function get()
	{
		return $this->newRoute('get');
	}

	public function post()
	{
		return $this->newRoute('post');
	}

	public function head()
	{
		return $this->newRoute('head');
	}

	public function patch()
	{
		return $this->newRoute('patch');
	}

	public function delete()
	{
		return $this->newRoute('delete');
	}

	public function put()
	{
		return $this->newRoute('put');
	}

	public function options()
	{
		return $this->newRoute('options');
	}

	public function any()
	{
		return $this->newRoute('any');
	}

	public function newRoute($method, $path = "")
	{
		$route = new Route($this, $routes, $method);

		if ($path)
		{
			$route->path($path);
		}

		$this->routes->add($route);

		return $route;
	}

	protected function compile(Route $route)
	{
		$route->path = $this->compilePath($route->path, $route->patterns);
	}

	public function compilePath($path, $clauses)
	{
		$variables = array();

		$path = preg_replace_callback('/{(.+?)}/', function ($matches) use ($clauses, &$variables)
		{
			// We'll check if there is one of the default patterns provided.
			if (strpos($matches[1], ':') !== false)
			{
				list($name, $pattern) = explode(':', $matches[1]);

				$pattern = ':' . $pattern;

				if (! array_key_exists($pattern, $this->patterns))
				{
					throw new \InvalidArgumentException("Invalid pattern given for route parameter [{$name}]");
				}

				else
				{
					$variables[$name] = "(".$this->patterns[$pattern].")";
				}
			}

			// If there isn't, we'll check if there is one chained onto the route.
			elseif (array_key_exists($matches[1], $clauses))
			{
				$variables[$matches[1]] = "(".$clauses[$matches[1]].")";
			}

			// If there isn't we'll fall back to the default pattern
			else
			{
				$variables[$matches[1]] = "(".$this->defaultPattern.")";
			}

			return '%s';

		}, $path);

		return str_replace(
			array_keys($this->patterns),
			$this->patterns,
			vsprintf('/'.preg_quote($path, '/').'/', $variables)
		);
	}
}