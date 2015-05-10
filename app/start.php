<?php

/*
|----------------------------------------------------------------------
| Define some usefull constants
|----------------------------------------------------------------------
|
| Eventhough composer autoloads a lot of our stuff, there will
| still be some files that are not autoloaded by composer, such
| as controllers. To make this easy on you, we will define some
| constants to the app directory, from which you can easily
| include stuff.
|
*/

define('APP', realpath(__DIR__));
define('ROOT', dirname(APP));
define('VENDOR', ROOT . '/vendor');


/*
|----------------------------------------------------------------------
| Include the composer autoloader
|----------------------------------------------------------------------
|
| Since composer conveniently provides an autoloader, we just
| have to include it to make use of it, and include all of our
| libraries automagically. Ez win, ez life.
|
*/

require VENDOR . '/autoload.php';