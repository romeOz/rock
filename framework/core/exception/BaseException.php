<?php
namespace rock\exception;

use rock\base\ClassName;
use rock\helpers\Helper;
use rock\helpers\String;
use rock\log\Log;
use rock\response\Response;
use rock\Rock;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Handler\XmlResponseHandler;

class BaseException extends \Exception implements ExceptionInterface
{
    use ClassName;

    public static $logged = true;
    public static $format = '{{message}} {{class}}::{{method}} {{file}} on line {{line}}';
    public static $asStack = false;
    protected static $pathFatal = '@common.views/layouts/fatal.html';

    /**
     * Constructor
     *
     * @param string     $msg         message
     * @param array      $placeholders placeholders for replacement
     * @param \Exception|null $handler     handler
     */
    public function __construct($msg, array $placeholders = [], \Exception $handler = null) {
        if (isset($handler)) {
            if ($handler instanceof BaseException) {
                return;
            }
            $msg = $msg ? : $handler->getMessage();
        }
        $this->message = $this->prepareMsg($msg, $placeholders);
        //parent::__construct($this->message);
        /** Add log */
        static::log(Log::CRITICAL, $this->message, $this->getTracesInternal($handler));
        /**  Display */
        $this->display($handler);
    }

    /**
     * Added to log
     *
     * @param int    $level
     * @param string $msg   error message
     * @param array  $traces data of trace
     *
     * - class:  name of class
     * - method: name of method
     * - file:   path to file
     * - line:   line fo file
     *
     * @return bool
     */
    public static function log($level = Log::CRITICAL, $msg, array $traces = [])
    {
        if (!static::$logged) {
            return true;
        }
        if (empty($traces)) {
            $traces = static::getTraces(2, static::$asStack);
        }

        return (new Log())->log($level, static::replace($msg, $traces, static::$asStack));
    }

    /**
     * Display fatal error
     */
    public static function displayFatal()
    {
        $response = Rock::$app->response;
        if ($response->getStatusCode() === 200) {
            $response->status500();
        }
        $response->send();
        if (Rock::$app->response->format !== Response::FORMAT_HTML) {
            echo 0;
            return;
        }
        if (!isset(static::$pathFatal) ||
            !file_exists(Rock::getAlias(static::$pathFatal))) {
            die('This site is temporarily unavailable. Please, visit the page later.');
        }

        die(file_get_contents(Rock::getAlias(static::$pathFatal)));
    }

    /**
     * Set path to fatal template
     *
     * @param string $path path to fatal template
     * @throws \Exception
     */
    public static function setPathFatal($path)
    {
        $path = Rock::getAlias($path);
        if (!file_exists($path)) {
            throw new \Exception("Unknown file: {$path}");
        }
        static::$pathFatal = $path;
    }

    /**
     * Run mode debug.
     *
     * @return \rock\exception\Run
     */
    public static function debuger()
    {
        $run = new Run();
        switch (Rock::$app->response->format) {
            case Response::FORMAT_JSON:
                $handler = new JsonResponseHandler();
                break;
            case Response::FORMAT_XML:
                $handler = new XmlResponseHandler();
                break;
            default:
                $handler = new PrettyPageHandler();
        }
        $response = Rock::$app->response;
        if ($response->getStatusCode() !== 200) {
            $run->setSendHttpCode($response->getStatusCode());
        }
        $run->pushHandler($handler);
        //$run->register();
        return $run;
    }

    /**
     * Get traces by `debug_backtrace`.
     *
     * @param int  $index
     * @param bool $asStack
     * @return array
     */
    public static function getTraces($index = 2, $asStack = DEBUG)
    {
        $trace  = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $class  = Helper::getValue($trace[$index]['class']);
        $method = Helper::getValue($trace[$index]['function']);
        $file   = $trace[$index - 1]['file'];
        $line   = $trace[$index - 1]['line'];
        $stack = null;
        if ($asStack === true) {
            ob_start();
            debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            $stack = str_replace("\n", ' ; ', ob_get_clean());
        }
        return [
            'class' => $class,
            'method' => $method,
            'file' => $file,
            'line' => $line,
            'stack' => $stack
        ];
    }

    public static function getTracesByException(\Exception $exception)
    {
        $trace           = $exception->getTrace();
        $traceAsString = $exception->getTraceAsString();
        $file            = $exception->getFile();
        $line            = $exception->getLine();
        $trace = current($trace);
        return [
            'class' => $trace['class'],
            'method' => $trace['function'],
            'file' => $file,
            'line' => $line,
            'stack' => str_replace("\n", ' ; ', $traceAsString)
        ];
    }

    public static function replace($message, array $traces, $asStack = DEBUG)
    {
        if ($asStack === true) {
            return "{$message} {$traces['stack']}";
        }

        $traces['message'] = $message;
        return String::replace(static::$format, $traces, false);
    }

    /**
     * Get traces by Exception handler.
     *
     * @param \Exception $exception
     * @return array
     */
    protected function getTracesInternal($exception)
    {
        if ($exception instanceof \Exception) {
            return static::getTracesByException($exception);
        }
        $trace           = $this->getTrace();
        $traceAsString  = $this->getTraceAsString();
        $file            = $this->getFile();
        $line            = $this->getLine();
        $trace = current($trace);
        return [
            'class' => $trace['class'],
            'method' => $trace['function'],
            'file' => $file,
            'line' => $line,
            'stack' => str_replace("\n", ' ; ', $traceAsString)
        ];
    }

    /**
     * Returns the prepared message.
     *
     * @param string|null $msg         error message
     * @param array      $placeholders placeholders for replacement
     * @return null|string
     */
    protected function prepareMsg($msg = null, array $placeholders = [])
    {
        if (isset($msg)) {
            return String::replace($msg, $placeholders);
        }

        return $msg;
    }

    /**
     * Display error.
     *
     * @param \Exception $handler
     */
    protected function display(\Exception $handler = null)
    {
        if (DEBUG === true) {
            static::debuger()->handleException(isset($handler) ? $handler : $this);
            return;
        }
        static::displayFatal();
    }

    /**
     * Converts an exception into a PHP error.
     *
     * This method can be used to convert exceptions inside of methods like `__toString()`
     * to PHP errors because exceptions cannot be thrown inside of them.
     *
     * @param \Exception $exception the exception to convert to a PHP error.
     */
    public static function convertExceptionToError($exception)
    {
        trigger_error(static::convertExceptionToString($exception), E_USER_ERROR);
    }

    /**
     * Converts an exception into a simple string.
     *
     * @param \Exception $exception the exception being converted
     * @return string the string representation of the exception.
    */
    public static function convertExceptionToString(\Exception $exception)
    {
        return static::replace($exception->getMessage(), static::getTracesByException($exception), static::$asStack);
    }
}