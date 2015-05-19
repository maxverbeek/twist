<?php namespace Twist\Slash\Exceptions;

use Twist\Slash\Exceptions\SlashSyntaxError;

class IndentationException extends SlashSyntaxError
{
	public function __construct($line, $context)
	{
		return parent::__construct("Indentation error: Invalid indentation", $line, $context);
	}
}