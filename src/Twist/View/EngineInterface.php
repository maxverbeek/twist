<?php namespace Twist\View;

interface EngineInterface
{
	public function get($path, array $data = array());
}