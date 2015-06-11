<?php namespace Twist\Container;

use ArrayAccess;
use Closure;
use ReflectionClass;
use ReflectionParameter;

class Container implements ArrayAccess
{
	/**
	 * A list of bindings that describe how
	 * every class should be instantiated
	 *
	 * @var array
	 */
	protected $bindings = [];

	/**
	 * All of the contextual bindings
	 *
	 * @var array
	 */
	protected $contextual = [];

	/**
	 * An array of instances that are currently bound.
	 *
	 * @var array
	 */
	protected $instances = [];

	/**
	 * A list of aliasses
	 *
	 * @var array
	 */
	protected $aliasses = [];

	/**
	 * List of classes that are being build at the moment
	 *
	 * @var array
	 */
	protected $buildstack = [];

	public function singleton($name, $instance)
	{
		$this->setAlias($name);

		$this->singletons[$name] = $instance;
	}

	/**
	 * Start a contextual clause
	 *
	 * @return \Twist\Container\ContextualHelper
	 */
	public function when($when)
	{
		$when = is_array($when) ? $when : func_get_args();

		return (new ContextualHelper($this))->when($when);
	}

	/**
	 * Add a contextual binding to the container
	 *
	 * @param array $whens
	 * @param array $needs
	 * @param mixed $give
	 */
	public function addContextual($whens, $needs, $give)
	{
		foreach ($whens as $when)
		{
			foreach ($needs as $need)
			{
				$this->contextual[$when][$need] = $give;
			}
		}
	}

	protected function getContextual($trigger)
	{
		$last = end($this->buildstack);

		if (isset($this->contextual[$last][$trigger]))
		{
			return $this->contextual[$last][$trigger];
		}
	}

	/**
	 * Extracts an alias, and sets it if there is one.
	 *
	 * @param mixed $alias
	 */
	protected function setAlias($alias)
	{
		if (is_array($alias))
		{
			$name = array_pop($alias);

			$this->alias($alias, $name);

			return $name;
		}

		return $alias;
	}

	public function alias($aliasses, $name)
	{
		foreach ((array) $aliasses as $alias)
		{
			$this->aliasses[$alias] = $name;
		}
	}

	public function make($name, $parameters = [])
	{
		$name = $this->getAlias($name);

		// If the object is a singleton, we'll return
		// it immediately, so that it isn't reinstatiated.
		if (isset($this->instances[$name]))
		{
			return $this->instances[$name];
		}

		$resolvable = $this->getClassResolver($name);

		if ($this->buildable($name, $resolvable))
		{
			// If we are ready to construct a new instance
			// of a given class, we'll do that now, using the
			// build-method.
			$object = $this->build($resolvable, $parameters);
		}

		else
		{
			// Otherwise, we'll try one more time, to see if
			// another class is bound.
			$object = $this->make($resolvable, $parameters);
		}

		// If the instance should be a singleton
		// we'll set that here, so that this process
		// is only done once.
		if ($this->isSingleton($name))
		{
			$this->instances[$name] = $object;
		}

		return $object;
	}

	protected function isSingleton($name)
	{
		return isset($this->bindings[$name]) && $this->bindings[$name]['singleton'];
	}

	protected function build($resolvable, $parameters)
	{
		// If the way to resolve this instance is
		// a Closure, we'll execute that immediately.
		if ($resolvable instanceof Closure)
		{
			return $resolvable($this, $parameters);
		}

		$reflection = new ReflectionClass($resolvable);

		// If we can not instantiate the given 'thing' we
		// will bail out, since there's nothing we can do.
		if (! $reflection->isInstantiable())
		{
			throw new DependencyResolveException("Failed to instantiate [$resolvable]");
		}

		// If the class has no constructor, it means
		// that it has no dependencies either, so
		// we can simply return a new instance.
		if (! $constructor = $reflection->getConstructor())
		{
			return $reflection->newInstance();
		}

		// We'll increment the buildstack, so that we can
		// use it to detect a loop. We also use this for
		// the contextual bindings
		$this->buildstack[] = $resolvable;

		// If the constructor has depedencies, we'll
		// resolve them, and turn them into objects.
		// Then we'll inject them into a new instance.
		$dependencies = $this->resolve($constructor->getParameters(), $parameters);

		// Decrement the buildstack, becouse we're done.
		array_pop($this->buildstack);

		return $reflection->newInstanceArgs($dependencies);
	}

	protected function resolve(array $parameters, array $replacements = array())
	{
		$result = [];

		foreach ($parameters as $parameter)
		{
			if (isset($replacements[$parameter->name]))
			{
				$result[] = $replacements[$parameter->name];

				continue;
			}

			$class = $parameter->getClass();

			// If the parameter is not typehinted, we
			// can't know what to do, so we'll check if
			// there is a default value set.
			if (! $class)
			{
				$result[] = $this->resolveNonClass($parameter);
			}

			else
			{
				$result[] = $this->resolveClass($class, $parameter, $replacements);
			}
		}

		return $result;
	}

	protected function resolveNonClass(ReflectionParameter $parameter)
	{
		if ($parameter->isDefaultValueAvailable())
		{
			return $parameter->getDefaultValue();
		}

		throw new DependencyResolveException("Parameter [$parameter->name] is not typehinted, and does not have a default value.");
	}

	protected function resolveClass(ReflectionClass $class, ReflectionParameter $parameter, array $parameters)
	{
		try
		{
			// We'll try to recursively resolve the dependency,
			// however, if this fails we'll fall back to the
			// default value.
			return $this->make($class->name, $parameters);
		}

		catch(DependencyResolveException $e)
		{
			if (! $parameter->isDefaultValueAvailable())
			{
				throw $e;
			}

			return $parameter->getDefaultValue();
		}
	}

	protected function getAlias($name)
	{
		if (isset($this->aliasses[$name]))
		{
			return $this->aliasses[$name];
		}

		return $name;
	}

	protected function getClassResolver($name)
	{
		// If there is a contextual binding set
		// for the current class, we'll swap out
		// the name for the class.
		if (NULL !== $resolver = $this->getContextual($name))
		{
			return $resolver;
		}

		// If the class was previously registered in
		// the container, we return that. Otherwise
		// we assume that the given name is the classname.
		if (isset($this->bindings[$name]))
		{
			return $this->bindings[$name]['resolvable'];
		}

		return $name;
	}

	protected function buildable($name, $resolvable)
	{
		// A class is constructable if it has a Closure
		// as a resolvable, or if the full classname is given.
		return $resolvable instanceof Closure || $name === $resolvable;
	}

	public function bind($class, $resolvable = null, $singleton = true)
	{
		$class = $this->setAlias($class);

		if ($resolvable === null)
		{
			$resolvable = $class;
		}

		$this->bindings[$class] = compact('resolvable', 'singleton');
	}

	public function bound($key)
	{
		return isset($this->instances[$key]) || isset($this->bindings[$key]);
	}

	public function remove($key)
	{
		unset($this->instances[$key]);
		unset($this->bindings[$key]);
	}

	public function __get($key)
	{
		return $this->make($key);
	}

	public function __set($key, $value)
	{
		return $this->bind($key, $value);
	}

	public function offsetSet($key, $value)
	{
		return $this->bind($key, $value);
	}

	public function offsetGet($key)
	{
		return $this->make($key);
	}

	public function offsetExists($key)
	{
		return $this->bound($key);
	}

	public function offsetUnset($key)
	{
		return $this->remove($key);
	}
}