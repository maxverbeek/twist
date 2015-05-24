<?php namespace Twist\Slash;

/**
 * The only purpose of this class is to match
 * the Slash standalone syntax to the Twist
 * view syntax for backward compatibility.
 */
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