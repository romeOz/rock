<?php
use rock\base\Alias;
use rock\Rock;

$composerAutoload = dirname(__DIR__) . '/vendor/autoload.php';
if (is_file($composerAutoload)) {
    /** @var \Composer\Autoload\ClassLoader $loader */
    $loader = require($composerAutoload);
}

$loader->addPsr4('rockunit\\', __DIR__);

defined('DEBUG')            or define('DEBUG', true);
defined('DS')               or define('DS', DIRECTORY_SEPARATOR);

$_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'] = 'site.com';
$_SERVER['HTTP_USER_AGENT'] = 'user';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SESSION = [];
//$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4';
date_default_timezone_set('UTC');

Rock::$app = new Rock();
Rock::$app->language = 'en';

if (!$config = require(dirname(__DIR__) . '/apps/common/configs/configs.php')) {
    die('configs is empty/not found');
}


Alias::setAlias('tests', __DIR__);
Alias::setAlias('rockunit', __DIR__);
Alias::setAlias('runtime', '@tests/runtime');

require(dirname(__DIR__) . '/framework/polyfills.php');

$components = require(__DIR__ . '/data/config.php');
$config['components'] = \rock\helpers\ArrayHelper::merge(
    $config['components'] ? : [],
    $components['classes']
);

Rock::$components = $config['components'];
unset($config['components']);
Rock::$config = $config;
\rock\di\Container::addMulti(Rock::$components);

\rock\exception\ErrorHandler::$logged = false;

Rock::$app->session->open();

