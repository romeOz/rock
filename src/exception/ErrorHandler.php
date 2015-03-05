<?php

namespace rock\exception;

use rock\base\Alias;
use rock\base\BaseException;
use rock\log\Log;
use rock\log\LogInterface;
use rock\response\Response;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Handler\XmlResponseHandler;

class ErrorHandler implements LogInterface
{
    public static $logged = true;
    public static $pathFatal = '@common.views/layouts/fatal.html';

    /**
     * Error handler
     *
     * @param int    $code
     * @param string $msg
     * @param string $file
     * @param int    $line
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
     * Fatal handler
     *
     * @return void
     */
    public static function handleShutdown()
    {
        $error = error_get_last();
        if (
            isset($error['type']) &&
            ($error['type'] == E_ERROR ||
             $error['type'] == E_PARSE ||
             $error['type'] == E_COMPILE_ERROR ||
             $error['type'] == E_CORE_ERROR)
        ) {
            // Clean buffer, complete work buffer
            ob_end_clean();
            //header($_SERVER['SERVER_PROTOCOL'].' 500 Internal Server Error');
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
     * Set handler
     */
    public static function run()
    {
        // Start buffer
        ob_start();
        $self = new static;
        // Catch errors
        set_error_handler([$self, 'handleError']);
        // Without try ... catch
        set_exception_handler(
            function () {
            }
        );
        // Catch fatal errors
        register_shutdown_function([$self, 'handleShutdown']);
    }

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
     * Display fatal error
     *
     * @param Response $response
     * @throws \Exception
     */
    public static function displayFatal(Response $response = null)
    {
        if (isset($response)) {
            if ($response->getStatusCode() === 200) {
                $response->status500();
            }
            $response->send();
            if ($response->format !== Response::FORMAT_HTML) {
                echo 0;
                return;
            }
        }

        if (!isset(static::$pathFatal) ||
            !file_exists(Alias::getAlias(static::$pathFatal))) {
            die('This site is temporarily unavailable. Please, visit the page later.');
        }

        die(file_get_contents(Alias::getAlias(static::$pathFatal)));
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
                    $handler = new PrettyPageHandler();
            }
            if ($response->getStatusCode() !== 200) {
                $run->setSendHttpCode($response->getStatusCode());
            }
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