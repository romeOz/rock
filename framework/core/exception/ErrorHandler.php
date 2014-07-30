<?php

namespace rock\exception;

use rock\log\LoggerInterface;


class ErrorHandler implements LoggerInterface
{
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
            /**
             * Clean buffer, complete work buffer
             */
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
                /**
                 * Display buffer, complete work buffer
                 */
                ob_end_flush();
            }
        }
    }

    protected static function log($level, $msg, $file, $line)
    {
        Exception::log($level, $msg, $file . ' on line ' . $line);
        if (DEBUG === true) {

            Exception::debuger()->handleException(new \ErrorException($msg, $level, 0, $file, $line));
        }
        if ($level > self::ERROR) {
            Exception::displayFatal();
        }
    }

    /**
     * Set handler
     */
    public static function run()
    {
        /**
         * Start buffer
         */
        ob_start();
        $self = new static;
        /**
         * Catch errors
         */
        set_error_handler([$self, 'handleError']);
        /**
         * Without try ... catch
         */
        set_exception_handler(
            function () {
            }
        );
        /**
         * Catch fatal errors
         */
        register_shutdown_function([$self, 'handleShutdown']);
    }


    /**
     * Converts an exception into a PHP error.
     *
     * This method can be used to convert exceptions inside of methods like `__toString()`
     * to PHP errors because exceptions cannot be thrown inside of them.
     * @param \Exception $exception the exception to convert to a PHP error.
     */
    public static function convertExceptionToError($exception)
    {
        trigger_error(static::convertExceptionToString($exception), E_USER_ERROR);
    }

    /**
     * Converts an exception into a simple string.
     * @param \Exception $exception the exception being converted
     * @return string the string representation of the exception.
     */
    public static function convertExceptionToString($exception)
    {
        if ($exception instanceof BaseException && !DEBUG/*&& ($exception instanceof UserException || !DEBUG)*/) {
            $message = "{$exception::className()}: {$exception->getMessage()}";
        } elseif (DEBUG) {
            if ($exception instanceof BaseException) {
                $message = "{$exception::className()}";
            } else {
                $message = 'Exception';
            }
            $message .= " '" . get_class($exception) . "' with message '{$exception->getMessage()}' \n\nin "
                        . $exception->getFile() . ':' . $exception->getLine() . "\n\n"
                        . "Stack trace:\n" . $exception->getTraceAsString();
        } else {
            $message = 'Error: ' . $exception->getMessage();
        }
        return $message;
    }
}