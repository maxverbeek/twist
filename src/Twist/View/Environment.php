<?php namespace Twist\View;

class Environment
{
	protected $blocks = [];

	protected $blockStack = [];

	protected $render = 0;

	public function setManager(Manager $manager)
	{
		$this->manager = $manager;
	}

	public function incrementRender()
	{
		$this->render++;
	}

	public function decrementRender()
	{
		$this->render--;
	}

	public function doneRendering()
	{
		return $this->render === 0;
	}

	public function flush()
	{
		$this->blocks = [];
		$this->blockStack = [];
	}

	public function flushIfDone()
	{
		if ($this->doneRendering())
		{
			$this->flush();
		}
	}

	public function make($view, $data)
	{
		return $this->manager->make($view, $data);
	}

	public function yieldBlock($name, $default)
	{
		return isset($this->blocks[$name]) ? $this->blocks[$name] : $default;
	}

	public function inject($name, $content)
	{
		if (isset($this->blocks[$name]))
		{
			$content = preg_replace($compiler->getParentTag(), $this->blocks[$name], $content);
		}

		$this->blocks[$name] = $content;
	}

	public function startBlock($name, $content = '')
	{
		if ($content === '')
		{
			if (ob_start()) $this->blockStack[] = $name;
		}

		else
		{
			$this->inject($name, $content);
		}
	}

	public function endBlock()
	{
		$last = array_pop($this->blockStack);

		$this->inject($last, ob_get_clean());
	}
}