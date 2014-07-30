<?php
/**
 * start timestamp by load application.
 */
use rock\request\Request;
use rock\Rock;

\rock\helpers\Trace::beginProfile(\rock\helpers\Trace::APP, \rock\helpers\Trace::TOKEN_APP_RUNTIME);
error_reporting(E_ALL | E_STRICT);
if (version_compare(PHP_VERSION, '5.4.0', '<')) {
    die('need to use PHP version 5.4.x or greater');
}
require('mixins.php');
defined('DEBUG') or define('DEBUG', true);
defined('DS') or define('DS', DIRECTORY_SEPARATOR);
Rock::$app = new Rock();

Rock::$app->language = Request::getPreferredLanguage(Rock::$app->allowLanguages);

/**
 * Catch error
 */
\rock\exception\ErrorHandler::run();
//
///**
// * Set level error
// */
//\models\exception\Exception::setLevelLog();
/**
 * bootstrap
 */
\rock\Rock::bootstrap($configs);



//
//if (ob_get_length() !== false) {
//    ob_end_flush();
//}


