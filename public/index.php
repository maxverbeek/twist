<?php

/**
 * Twist Framework - Ez win, ez life
 *
 * @package  Twist
 * @author   Max Verbeek <m4xv3rb33k@gmail.com>
 * @version  2.0
 */

/*
|--------------------------------------------------------------------------
| Initialize the environment
|--------------------------------------------------------------------------
|
| To make life easy on ourselves, we boot autoloaders and define
| some usefull constants. This allows for easy including without
| having trouble when moving files around
|
*/

require '../app/start.php';


/*
|--------------------------------------------------------------------------
| Create the application
|--------------------------------------------------------------------------
|
| The core logic is stored in the application class, so we'll
| make that here.
|
*/

use Twist\Core\Application;

$app = new Application();

function app($app = null)
{
	static $instance;

	if ($instance === null) // first call
	{
		$instance = $app;
	}

	return $app === null ? $instance : $instance[$app];
}

app($app);


/*
|--------------------------------------------------------------------------
| Set up config
|--------------------------------------------------------------------------
|
| We initialize our config repository early, so
| that we can make use of it throughout this entire file.
|
*/

use Twist\Config\Config;

$app['config'] = new Config(APP . '/config');


/*
|--------------------------------------------------------------------------
| Create the kernel
|--------------------------------------------------------------------------
|
| The HTTP Kernel will provide us with a layer
| that we can populate with middleware. We
| create this before the routing, so that we can append
| middleware during the routing proces.
|
*/

use Twist\Core\Kernel;

$kernel = new Kernel($app);

$app['kernel'] = $kernel;


/*
|--------------------------------------------------------------------------
| Set up routing
|--------------------------------------------------------------------------
|
| Here we prepare the router, and bind all of the routes
| to our application.
|
*/

use Twist\Routing\Router;

$app['router'] = new Router();

function route()
{
	$router = app()->router;

	return call_user_func_array([$router, 'newRoute'], func_get_args());
}

require $app['config']['routing.routes'];


/*
|--------------------------------------------------------------------------
| Handle the request
|--------------------------------------------------------------------------
|
| We create an abstract request, and pass this to the kernel
| which converts it to a response. We'll send this response
| to the client.
|
*/

use Twist\Http\Request;

$request = new Request();

with($response = $kernel->handle($request))->send();


/*
|--------------------------------------------------------------------------
| Terminate middleware
|--------------------------------------------------------------------------
|
| When everything is done, we'll give the middleware
| a final chance to perform logic after the request is sent.
|
*/

$kernel->terminate($request, $response);
