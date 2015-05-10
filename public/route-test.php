<?php

namespace Twist\Routing;

require '../vendor/autoload.php';

$router = new Router;

var_dump($router->compilePath('{jemoeder:alnum}', ['asdf' => '[0-9a-zA-Z]+']));