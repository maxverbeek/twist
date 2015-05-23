<?php namespace Twist\Slash;

class Renderable
{
	public function __construct($content)
	{
		$this->content = $content;
	}

	public function render()
	{
		return $this->content;
	}
}