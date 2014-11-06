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
        BaseException::log($level, $msg, ['file' => $file, 'line' => $line, 'stack' => $file . ' on line ' . $line]);
        if (DEBUG === true) {

            BaseException::debuger()->handleException(new \ErrorException($msg, $level, 0, $file, $line));
        }
        if ($level > self::ERROR) {
            BaseException::displayFatal();
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
}