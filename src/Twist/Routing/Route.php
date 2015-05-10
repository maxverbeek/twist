<?php namespace Twist\Routing;

class Route
{
	public $method;

	public $path;

	/**
	 * Holds the uncompiled path, usefull for URL generation.
	 *
	 * @var string
	 */
	public $original_path;

	public $name;

	public $middleware;

	public function __construct($method)
	{
		$this->method($method);
	}

	public function method($method)
	{
		if ($method === 'any')
		{
			$this->method = array('GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS');
		}

		else
		{
			$this->method = array(strtoupper($method));
		}

		return $this;
	}

	public function addMethod($method)
	{
		$this->method[] = strtoupper($method);

		return $this;
	}

	public function path($path)
	{
		$this->original_path = $this->path = trim($path, '/');

		return $this;
	}

	public function name($name)
	{
		$this->name = $name;

		return $this;
	}

	public function as($name)
	{
		return $this->name($name);
	}

	public function middleware($middleware)
	{
		$this->middleware = $middleware;

		return $this;
	}

	public function where($name, $pattern)
	{
		$this->patterns[$name] = $pattern;

		return $this;
	}
}