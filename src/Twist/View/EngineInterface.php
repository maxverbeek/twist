<?php namespace Twist\View;

interface EngineInterface
{
	public function get($data, array $path = array());
}