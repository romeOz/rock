<?php
namespace rock\exception;

use rock\base\ClassName;
use rock\helpers\ArrayHelper;
use rock\helpers\Helper;
use rock\helpers\String;
use rock\log\Log;
use rock\log\LoggerInterface;
use rock\response\Response;
use rock\Rock;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Handler\XmlResponseHandler;

abstract class BaseException extends \Exception implements LoggerInterface, ExceptionInterface
{
    use ClassName;

    /**
     * Error Level
     *
     * @var int
     */
    protected static $level = self::INFO;
    /**
     * Array of exceptions
     *
     * @var array
     */
    protected static $_exceptions = [];

    protected static $pathFatal = '@common.views/layouts/fatal.html';

    /**
     * Constructor
     *
     * @param int|string      $level       - type of exception
     * @param string|null     $msg         - message or code
     * @param array|null      $dataReplace - array replace
     * @param \Exception|null $handler     - handler
     */
    public function __construct($level = self::ERROR, $msg = null, array $dataReplace = [], \Exception $handler = null) {
        if (isset($handler)) {
            if (method_exists($handler, 'setLevelLog')) {
                return;
            }
            $msg = $msg ? : $handler->getMessage();
        }
        $this->message = $this->prepareMsg($msg, $dataReplace);
        //parent::__construct($this->message);
        /** Add log */
        static::log($level, $this->message, $this->_inlineTrace($handler));
        /**  Display */
        $this->display($level, $this->message, $handler);
    }



    /**
     * log
     *
     * @param string $msg         - message
     * @param int    $level
     * @param array  $trace       - data of trace
     *                            => class    - name of class
     *                            => method   - name of method
     *                            => file     - path to file
     *                            => line     - line fo file
     * @return bool
     */
    public static function log($level = self::ERROR, $msg, $trace = null)
    {
        $msg .= ' ' . (!isset($trace) ? static::inlineBacktrace() : $trace);
        return (new Log())->log($level, $msg);
    }

    /**
     * Display fatal error
     */
    public static function displayFatal()
    {
        $response = new Response();
        if ($response->getStatusCode() === 200) {
            $response->status500();
        }
        $response->send();
        if (!isset(static::$pathFatal) ||
            !file_exists(Rock::getAlias(static::$pathFatal))
        ) {
            die('This site is temporarily unavailable. Please, visit the page later.');
        }

        die(file_get_contents(Rock::getAlias(static::$pathFatal)));
    }


    /**
     * Get exception
     *
     * @param string $name - key
     * @return array|string
     */
    public static function get($name)
    {
        return Helper::getValue(self::$_exceptions[$name]);
    }

    /**
     * Get all exceptions
     *
     * @param array $only
     * @param array $exclude
     * @return array
     */
    public static function getAll(array $only = [], array $exclude = [])
    {
        return ArrayHelper::prepareArray(self::$_exceptions, $only, $exclude);
    }

    /**
     * Set level log
     *
     * @param int $level
     */
    public static function setLevelLog($level)
    {
        static::$level = (int)$level;
    }

    /**
     * Set path by fatal page
     * @param string $path
     * @throws Exception
     */
    public static function setPathFatal($path)
    {
        $path = Rock::getAlias($path);
        if (!file_exists($path)) {
            throw new Exception(Exception::CRITICAL, Exception::UNKNOWN_FILE, ['path' => $path]);
        }

        static::$pathFatal = $path;
    }

    /**
     * Run mode debug
     */
    public static function debuger()
    {
        $run = new Run();
        switch (Response::$format) {
            case Response::FORMAT_JSON:
                $handler = new JsonResponseHandler();
                break;
            case Response::FORMAT_XML:
                $handler = new XmlResponseHandler();
                break;
            default:
                $handler = new PrettyPageHandler();
        }
        $response = new Response();
        if ($response->getStatusCode() !== 200) {
            $run->setSendHttpCode($response->getStatusCode());
        }
        $run->pushHandler($handler);
        //$run->register();
        return $run;
    }

    /**
     * Get trace
     *
     * @return string
     */
    protected static function inlineBacktrace()
    {
        /**
         * Trace methods
         */
        $dataTrace      = debug_backtrace(-2);
        $data           = [];
        $data['class']  = Helper::getValue($dataTrace[2]['class']);
        $data['method'] = Helper::getValue($dataTrace[2]['function']);
        $data['file']   = $dataTrace[1]['file'];
        $data['line']   = $dataTrace[1]['line'];
        if (DEBUG === true) {
            ob_start();
            debug_print_backtrace(-2);
            $data['trace'] = str_replace("\n", ' ; ', ob_get_clean());
            return $data;
        }
        return $data['class'] . '	' .
               $data['method'] . '	' .
               $data['file'] . ' on line ' .
               $data['line'];
    }


    /**
     * Get trace as string
     *
     * @param object $handler
     * @return string
     */
    protected function _inlineTrace($handler)
    {
        $trace           = $this->getTrace();
        $traceAsString  = $this->getTraceAsString();
        $file            = $this->getFile();
        $line            = $this->getLine();
        if ($handler instanceof \Exception) {
            $trace           = $handler->getTrace();
            $traceAsString = $handler->getTraceAsString();
            $file            = $handler->getFile();
            $line            = $handler->getLine();
        }
        $array = current($trace);
        if (DEBUG === true) {
            return str_replace("\n", ' ; ', $traceAsString);
        }
        return implode(' ', [$array['class'].'::'.$array['function'], $file . ' on line', $line]);
    }

    /**
     * Get log message
     *
     * @param string|null $msg         - message
     * @param array|null  $dataReplace - array replace
     * @return null|string
     */
    protected function prepareMsg($msg = null, array $dataReplace = [])
    {
        if (isset($msg)) {
            return String::replace($msg, $dataReplace);
        }

        return $msg;
    }



    /**
     * Display error
     *
     * @param string     $msg       - message
     * @param int        $level     - type of exception
     *                              => FATAL
     *                              => WARNING
     *                              => ERROR
     *                              => INFO
     * @param \Exception $handler
     */
    protected function display($level = self::ERROR, $msg, \Exception $handler = null)
    {
        if ($level > static::$level) {
            if (DEBUG === true || Response::$format !== Response::FORMAT_HTML) {
                static::debuger()->handleException(isset($handler) ? $handler : $this);
                return;
            }
            static::displayFatal();
        }
        $className = explode('\\', get_class($this));
        $className = end($className);
        if (isset($code)) {
            self::$_exceptions[$className][$code] = $msg;
            return;
        }
        self::$_exceptions[$className][] = $msg;
    }
}