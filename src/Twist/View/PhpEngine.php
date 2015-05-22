<?php namespace Twist\View;

use Exception;

class PhpEngine implements EngineInterface
{
	public function get($path, array $data = array())
	{
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

		return ltrim(ob_get_clean(), PHP_EOL);
	}
}