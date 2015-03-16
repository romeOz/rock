<?php
namespace rock;


use rock\base\Alias;
use rock\base\ClassName;
use rock\base\ObjectInterface;
use rock\di\Container;
use rock\events\Event;
use rock\exception\ErrorHandler;
use rock\helpers\Trace;
use rock\i18n\i18n;
use rock\log\Log;

class Rock extends Alias
{
    use ClassName;
    /**
     *  @event AppEvent an event that is triggered at the beginning
     */
    const EVENT_BEGIN_APP = 'beginApp';
    /**
     *  @event AppEvent an event that is triggered at the end
     */
    const EVENT_END_APP = 'endApp';

    /**
     * @var RockInterface
     */
    public static $app;
    /**
     * Directory to framework.
     * @var string
     */
    public static $dir = __DIR__;
    /**
     * @var string the charset currently used for the application.
     */
    public $charset = 'UTF-8';
    /**
     * @var string the language that is meant to be used for end users.
     * @see sourceLanguage
     */
    public $language = 'en';
    /**
     * A list of the languages supported by the application.
     * @var array
     */
    public $allowLanguages = [];
    /**
     * Config application.
     * @var array
     */
    public static $config = [];
    /**
     * Components used by the Rock autoloading mechanism.
     * @var array
     */
    public static $components = [];
    /**
     * Current controller
     *
     * @var \rock\core\Controller
     */
    public $controller;

    /**
     * Bootstrap
     *
     * @param array $config
     * @throws \rock\base\BaseException
     */
    public static function bootstrap(array $config)
    {
            Trace::beginProfile(Trace::APP, Trace::TOKEN_APP_RUNTIME);

            static::$components = $config['components'];
            unset($config['components']);
            static::$config = $config;
            Container::addMulti(static::$components);

            $response = static::$app->response;
        try {
            // Triggered at the beginning
            Event::trigger(static::className(), self::EVENT_BEGIN_APP);

            // Routing
            $route = static::$app->route;
            $route->response = $response;

            $route->run();
        } catch (\Exception $e) {
            ErrorHandler::display($e, Log::CRITICAL, $response);
        }
        //var_dump(Trace::getTime(Trace::APP_TIME));
        Trace::endProfile(Trace::APP, Trace::TOKEN_APP_RUNTIME);
        //var_dump(Trace::get('db.query'), Trace::get(\rock\helpers\Trace::APP));

        // Triggered at the end
        Event::trigger(static::className(), self::EVENT_END_APP);

        $response->send();
    }

    /**
     * Creates a new object using the given configuration.
     *
     * The configuration can be either a string or an array.
     * If a string, it is treated as the *object class*; if an array,
     * it must contain a `class` element specifying the *object class*, and
     * the rest of the name-value pairs in the array will be used to initialize
     * the corresponding object properties.
     *
     * Below are some usage examples:
     *
     * ```php
     * $object = Rock::factory('\rock\db\Connection');
     * $object = Rock::factory(\rock\db\Connection::className());
     * $object = Rock::factory([
     *     'class' => '\rock\db\Connection',
     *     'dsn' => $dsn,
     *     'username' => $username,
     *     'password' => $password,
     * ]);
     * $object = Rock::factory($arg1, $arg2, [
     *     'class' => 'apps\frontend\FooController',
     *     'test' => 'test',
     * ]);
     * ```
     *
     *
     * This method can be used to create any object as long as the object's constructor is
     * defined like the following:
     *
     * ```php
     * public function __construct(..., $config = []) {
     * }
     * ```
     *
     * The method will pass the given configuration as the last parameter of the constructor,
     * and any additional parameters to this method will be passed as the rest of the constructor parameters.
     *
     * @param mixed ...$args arguments for constructor.
     * @param string|array $config the configuration. It can be either a string representing the class name
     *                             or an array representing the object configuration.
     * @param mixed $throwException throws exception
     * @return ObjectInterface|null the created object
     */
    public static function factory(/*$args...*/$config, $throwException = true)
    {
        return call_user_func_array([Container::className(), 'load'], func_get_args());
    }

    /**
     * Get instance of object
     *
     * @param string $class name of class
     * @return null|object
     */
    public function __get($class)
    {
        return self::factory($class);
    }

    /**
     * Translate
     *
     * @param string|array  $keys chain keys
     * @param array $placeholders
     * @param string|null  $category
     * @param string $locale
     * @return null|string
     */
    public static function t($keys, array $placeholders = [], $category = null, $locale = null)
    {
        return i18n::t($keys, $placeholders, $category, $locale);
    }

    /**
     * Add trace data
     *
     * ```php
     * Rock::trace('db', ['dsn' => ..., 'query' => ...]);
     *
     * Rock::trace(__METHOD__, 'text');
     * ```
     *
     * @param string $category
     * @param mixed  $token
     */
    public static function trace($category, $token)
    {
        Trace::trace($category, $token);
    }

    /**
     * Begin profile.
     *
     * @param $category
     * @param $token
     */
    public static function beginProfile($category, $token)
    {
        Trace::beginProfile($category, $token);
    }

    /**
     * End profile.
     *
     * @param $category
     * @param $token
     */
    public static function endProfile($category, $token)
    {
        Trace::endProfile($category, $token);
    }

    /**
     * Logging as `INFO`
     *
     * @param string $message
     * @param array  $placeholders placeholders for replacement
     */
    public static function info($message, array $placeholders = [])
    {
        Log::log(Log::INFO, $message, $placeholders);
    }

    /**
     * Logging as `DEBUG`
     *
     * @param string $message
     * @param array  $placeholders placeholders for replacement
     */
    public static function debug($message, array $placeholders = [])
    {
        Log::log(Log::DEBUG, $message, $placeholders);
    }

    /**
     * Logging as `WARNING`
     *
     * @param string $message
     * @param array  $placeholders placeholders for replacement
     */
    public static function warning($message, array $placeholders = [])
    {
        Log::log(Log::WARNING, $message, $placeholders);
    }

    /**
     * Logging as `ERROR`
     *
     * @param string $message
     * @param array  $placeholders placeholders for replacement
     */
    public static function error($message, array $placeholders = [])
    {
        Log::log(Log::ERROR, $message, $placeholders);
    }

    /**
     * Logging as `CRITICAL`
     *
     * @param string $message
     * @param array  $placeholders placeholders for replacement
     */
    public static function crit($message, array $placeholders = [])
    {
        Log::log(Log::CRITICAL, $message, $placeholders);
    }
}