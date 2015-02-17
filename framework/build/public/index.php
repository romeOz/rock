<?php
use rock\Rock;

error_reporting(-1);
if (version_compare(PHP_VERSION, '5.4.0', '<')) {
    die('need to use PHP version 5.4.x or greater');
}

require(dirname(__DIR__) . '/vendor/autoload.php');

defined('ROCK_DEBUG') or define('ROCK_DEBUG', true);
defined('DS') or define('DS', DIRECTORY_SEPARATOR);
require(dirname(__DIR__) . '/framework/polyfills.php');

Rock::$app = new Rock();

// catch error
\rock\exception\ErrorHandler::run();

$config = require(dirname(__DIR__) .'/apps/frontend/configs/configs.php');

// bootstrap application
\rock\Rock::bootstrap($config);

