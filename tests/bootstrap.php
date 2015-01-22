<?php
use rock\execute\CacheExecute;
use rock\log\Log;
use rock\rbac\PhpManager;
use rock\Rock;
use rockunit\core\session\mocks\SessionMock;
use rockunit\mocks\CookieMock;

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
$_SESSION = [];
//$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4';
date_default_timezone_set('UTC');

if (!$config = require(dirname(__DIR__) . '/apps/common/configs/configs.php')) {
    die('configs is empty/not found');
}


Rock::setAlias('tests', __DIR__);
Rock::setAlias('rockunit', __DIR__);
Rock::setAlias('runtime', '@tests/runtime');

Rock::$app = new Rock();
Rock::$app->language = \rock\i18n\i18nInterface::EN;

require(dirname(__DIR__) . '/framework/polyfills.php');

$config['components'] = \rock\helpers\ArrayHelper::merge(
    $config['components'] ? : [],
    [
        'log' => [
            'class' => Log::className(),
            'path' => __DIR__ . '/runtime/logs'
        ],
        'cache' => [
            'class' => \rock\cache\CacheStub::className()
        ],
        'session' => [
            'class' => SessionMock::className(),
        ],
        'cookie' => [
            'class' => CookieMock::className(),
        ],
        'execute' => [
            'class' => CacheExecute::className(),
            'path' => '@tests/runtime/cache/_execute'
        ],
        'rbac' => [
            'class' => PhpManager::className(),
            'path' => '@tests/core/rbac/src/rbac.php'
        ]
    ]
);

Rock::$components = $config['components'];
unset($config['components']);
Rock::$config = $config;
\rock\di\Container::addMulti(Rock::$components);

\rock\exception\BaseException::$logged = false;

Rock::$app->session->open();

