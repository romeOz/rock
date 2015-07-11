<?php

namespace rock\exception;

use rock\base\Alias;
use rock\base\BaseException;
use rock\log\Log;
use rock\log\LogInterface;
use rock\request\Request;
use rock\response\Response;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Handler\XmlResponseHandler;

class ErrorHandler implements LogInterface
{
    public static $logged = true;
    public static $pathFatal = '@common.views/layouts/fatal.html';
    /**
     * @var integer the size of the reserved memory. A portion of memory is pre-allocated so that
     * when an out-of-memory issue occurs, the error handler is able to handle the error with
     * the help of this reserved memory. If you set this value to be 0, no memory will be reserved.
     * Defaults to 256KB.
     */
    public static $memoryReserveSize = 262144;
    /**
     * @var string Used to reserve memory for fatal error handler.
     */
    private static $_memoryReserve;

    /**
     * Register this error handler.
     */
    public static function register()
    {
        // Start buffer
        ob_start();
        $class = get_called_class();
        // Catch errors
        set_error_handler([$class, 'handleError']);
        // Without try ... catch
        set_exception_handler(
            function () {
            }
        );
        if (static::$memoryReserveSize > 0) {
            self::$_memoryReserve = str_repeat('x', static::$memoryReserveSize);
        }
        // Catch fatal errors
        register_shutdown_function([$class, 'handleShutdown']);
    }

    /**
     * Unregisters this error handler by restoring the PHP error and exception handlers.
     */
    public static function unregister()
    {
        restore_error_handler();
        restore_exception_handler();
    }

    /**
     * Error handler.
     *
     * @param int $code
     * @param string $msg
     * @param string $file
     * @param int $line
     * @return bool
     */
    public static function handleError($code, $msg, $file, $line)
    {
        if (~error_reporting() & $code) {
            return false;
        }
        switch ($code) {
            case E_USER_WARNING:
            case E_WARNING:
                static::log(self::WARNING, "[E_WARNING] {$msg}", $file, $line);
                break;
            case E_USER_NOTICE:
            case E_NOTICE:
            case @E_STRICT:
                static::log(self::NOTICE, "[E_NOTICE] {$msg}", $file, $line);
                break;
            case @E_RECOVERABLE_ERROR:
                static::log(self::ERROR, "[E_CATCHABLE] {$msg}", $file, $line);
                break;
            default:
                static::log(self::CRITICAL, "[E_UNKNOWN] {$msg}", $file, $line);
                break;
        }

        return true;
    }

    /**
     * Fatal handler.
     *
     * @return void
     */
    public static function handleShutdown()
    {
        self::$_memoryReserve = null;

        $error = error_get_last();
        if (
            isset($error['type']) &&
            ($error['type'] == E_ERROR ||
                $error['type'] == E_PARSE ||
                $error['type'] == E_COMPILE_ERROR ||
                $error['type'] == E_CORE_ERROR)
        ) {
            // Clean buffer, complete work buffer
            static::clearOutput();

            $type = "";
            switch ($error['type']) {
                case E_ERROR:
                    $type = '[E_ERROR] ';
                    break;
                case E_PARSE:
                    $type = '[E_PARSE] ';
                    break;
                case E_COMPILE_ERROR:
                    $type = '[E_COMPILE_ERROR] ';
                    break;
                case E_CORE_ERROR:
                    $type = '[E_CORE_ERROR] ';
                    break;
            }
            static::log(self::CRITICAL, $type . $error['message'], $error['file'], $error['line']);
        } else {
            if (ob_get_length() !== false) {
                // Display buffer, complete work buffer
                ob_end_flush();
            }
        }
    }

    /**
     * @param \Exception $exception
     * @param int $level
     * @param Response $response
     */
    public static function display(\Exception $exception, $level = Log::CRITICAL, Response $response = null)
    {
        // append log
        if (static::$logged) {
            Log::log($level, BaseException::convertExceptionToString($exception));
        }

        // display Whoops
        if (ROCK_DEBUG === true) {
            static::debuger($response)->handleException($exception);
            return;
        }

        // else display fatal
        static::displayFatal($response);
    }

    /**
     * Display fatal error.
     *
     * @param Response $response
     * @throws \Exception
     */
    public static function displayFatal(Response $response = null)
    {
        if (isset($response)) {
            $response->setStatusCode(500);
            $response->send();
            $request = new Request();
            if ($response->format !== Response::FORMAT_HTML || $request->isAjax() || $request->isCORS()) {
                echo 0;
                return;
            }
        }

        if (!isset(static::$pathFatal) ||
            !file_exists(Alias::getAlias(static::$pathFatal))
        ) {
            die('This site is temporarily unavailable. Please, visit the page later.');
        }

        die(file_get_contents(Alias::getAlias(static::$pathFatal)));
    }

    /**
     * Removes all output echoed before calling this method.
     */
    public static function clearOutput()
    {
        // the following manual level counting is to deal with zlib.output_compression set to On
        for ($level = ob_get_level(); $level > 0; --$level) {
            if (!@ob_end_clean()) {
                ob_clean();
            }
        }
    }


    /**
     * Run mode debug.
     *
     * @param Response $response
     * @return Run
     */
    protected static function debuger(Response $response = null)
    {
        $run = new Run();

        if (isset($response)) {
            switch ($response->format) {
                case Response::FORMAT_JSON:
                    $handler = new JsonResponseHandler();
                    break;
                case Response::FORMAT_XML:
                    $handler = new XmlResponseHandler();
                    break;
                default:
                    $request = new Request();
                    if ($request->isAjax() || $request->isCORS()) {
                        $handler = new JsonResponseHandler();
                    } else {
                        $handler = new PrettyPageHandler();
                    }

            }
            $run->setSendHttpCode(500);
            $response->setStatusCode(500);
            $response->send();
        } else {
            $handler = new PrettyPageHandler();
        }

        $run->pushHandler($handler);
        //$run->register();
        return $run;
    }

    protected static function log($level, $msg, $file, $line)
    {
        static::display(new \ErrorException($msg, $level, 0, $file, $line), $level);
    }
}