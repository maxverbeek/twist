<?php namespace Twist\View;

class View
{
	/**
	 * The name of the view being rendered
	 *
	 * @var string
	 */
	protected $view;

	/**
	 * The data of the view being rendered
	 *
	 * @var array
	 */
	protected $data;

	/**
	 * The path to the view being rendered
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * The blocks in the view
	 *
	 * @var array
	 */
	protected $blocks = [];

	/**
	 * The current open blocks
	 *
	 * @var array
	 */
	protected $blockStack = [];

	public function __construct($view, $data, $path, $extension, Manager $manager)
	{
		$this->view = $view;
		$this->data = $data;
		$this->path = $path;
		$this->extension = $extension;
		$this->manager = $manager;
	}

	/**
	 * Yield a block from the finished blocks
	 *
	 * @param  string $name
	 * @param  string $fallback [description]
	 *
	 * @return string
	 */
	public function yieldBlock($name, $fallback = '')
	{
		if (isset($this->blocks[$name]))
		{
			return $this->blocks[$name];
		}

		return $fallback;
	}

	/**
	 * Begin a new block
	 *
	 * @param  string $name
	 * @param  string $content [description]
	 * @param  string $parent
	 *
	 * @return void
	 */
	public function startBlock($name, $content = '', $parent = '')
	{
		if ($content !== '')
		{
			return $this->inject($name, $content, $parent);
		}

		if (ob_start()) $this->blockStack[] = $name;
	}

	/**
	 * End an open block
	 *
	 * @param  string $parent
	 *
	 * @return void
	 */
	public function endBlock($parent = '')
	{
		$last = array_pop($this->blockStack);

		$this->inject($last, ob_get_clean(), $parent);
	}

	/**
	 * Inject a block of content in the blocks array
	 *
	 * @param  string $name
	 * @param  string $content
	 * @param  string $parent
	 *
	 * @return void
	 */
	public function inject($name, $content = '', $parent = '')
	{
		if ($parent && isset($this->blocks[$name]))
		{
			$content = preg_replace($parent, $this->blocks[$name], $content);
		}

		$this->blocks[$name] = $content;
	}

	/**
	 * Include a sub-view in the current view
	 *
	 * @param  string $viewname
	 * @param  array $defined  defined variables in the view
	 *
	 * @return string
	 */
	public function includeView($viewname, $defined)
	{
		return $this->manager->getEvaluated($viewname, $this, $defined);
	}

	/**
	 * Add a piece of data on the view
	 *
	 * @param  string $key
	 * @param  mixed $value
	 *
	 * @return $this
	 */
	public function with($key, $value)
	{
		$this->data[$key] = $value;

		return $this;
	}

	/**
	 * Render the view
	 *
	 * @return string
	 */
	public function render()
	{
		$this->data['__env'] = $this;

		$contents = $this->manager->getEngine($this->extension)->get($this->path, $this->data);

		$this->flush();

		return $contents;
	}

	/**
	 * Reset the blocks array
	 *
	 * @return void
	 */
	protected function flush()
	{
		$this->blocks = [];

		foreach ($this->blockStack as $openBlock)
		{
			ob_end_clean();
		}
	}
}