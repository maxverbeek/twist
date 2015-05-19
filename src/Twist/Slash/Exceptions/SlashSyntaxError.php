<?php namespace Twist\Slash\Exceptions;

class SlashSyntaxError extends \InvalidArgumentException
{
	public function __construct($message, $line, $context)
	{
		$message = $message . ' on line ' . $line . ' "' . $context . '"';

		return parent::__construct($message);
	}
}