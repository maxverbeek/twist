<?php namespace Twist\View;

use Exception;

class PhpEngine implements EngineInterface
{
	public function get($path, array $data = array())
	{
		if (isset($data['__path'])) unset($data['__path']);
		if (isset($data['__data'])) unset($data['__data']);

		return $this->createContents($path, $data);
	}

	protected function createContents($__path, $__data)
	{
		$oblevel = ob_get_level();

		extract($__data);

		ob_start();

		try
		{
			require $__path;
		}

		catch(Exception $e)
		{
			while ($oblevel > ob_get_level())
			{
				ob_end_clean();
			}

			throw $e;
		}

		return ltrim(ob_get_clean(), "\r\n");
	}
}