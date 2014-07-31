<?php

namespace rock\log;


use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use rock\base\ComponentsTrait;
use rock\base\ObjectTrait;
use rock\helpers\String;
use rock\Rock;

class Log implements LoggerInterface
{
    use ComponentsTrait {
        ComponentsTrait::__construct as parentConstruct;
    }

    public static $path = '@runtime/logs';

    /** @var Logger  */
    protected static $logger;

    public function __construct($config = [])
    {
        $this->parentConstruct($config);

        static::$logger = new Logger('Rock');

        $path = Rock::getAlias(static::$path);
        if (!file_exists($path)) {
            mkdir($path);
        }
        $formatter = new LineFormatter("[%datetime%]\t%level_name%\t%extra.hash%\t%message%\t%extra.user_id%\t%extra.user_ip%\t%extra.user_agent%\n");
        static::$logger->pushProcessor(function ($record) {
                $record['extra']['hash'] = substr(md5($record['message']), -6);
                $record['extra']['user_agent'] = strip_tags($_SERVER['HTTP_USER_AGENT']);
                $record['extra']['user_ip'] = filter_input(INPUT_SERVER, 'REMOTE_ADDR', FILTER_VALIDATE_IP);
                $record['extra']['user_id'] = isset($_SESSION['user']['id']) ? $_SESSION['user']['id'] : 0;
                return $record;
            });
        static::$logger->pushHandler((new StreamHandler("{$path}/debug.log", self::DEBUG, false))->setFormatter($formatter));
        static::$logger->pushHandler((new StreamHandler("{$path}/info.log", self::INFO, false))->setFormatter($formatter));
        static::$logger->pushHandler((new StreamHandler("{$path}/error.log", self::NOTICE, false))->setFormatter($formatter));
        static::$logger->pushHandler((new StreamHandler("{$path}/error.log", self::WARNING, false))->setFormatter($formatter));
        static::$logger->pushHandler((new StreamHandler("{$path}/error.log", self::ERROR, false))->setFormatter($formatter));
        static::$logger->pushHandler((new StreamHandler("{$path}/error.log", self::CRITICAL, false))->setFormatter($formatter));
        static::$logger->pushHandler((new StreamHandler("{$path}/error.log", self::ALERT, false))->setFormatter($formatter));
        static::$logger->pushHandler((new StreamHandler("{$path}/error.log", self::EMERGENCY, false))->setFormatter($formatter));
    }

    public static function setPath($path)
    {
        static::$path = $path;
    }

    /**
     * Adds a log record at an arbitrary level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  mixed   $level   The log level
     * @param  string  $message The log message
     * @param  array   $dataReplace The log context
     * @return bool Whether the record has been processed
     */
    public function log($level, $message, array $dataReplace = [])
    {
        return static::$logger->log($level, String::replace($message, $dataReplace), $dataReplace);
    }

    /**
     * Adds a log record at the DEBUG level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $dataReplace The log context
     * @return Boolean Whether the record has been processed
     */
    public function debug($message, array $dataReplace = [])
    {
        return static::$logger->debug(String::replace($message, $dataReplace), $dataReplace);
    }

    /**
     * Adds a log record at the INFO level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $dataReplace The log context
     * @return Boolean Whether the record has been processed
     */
    public function info($message, array $dataReplace = [])
    {
        return static::$logger->info(String::replace($message, $dataReplace), $dataReplace);
    }

    /**
     * Adds a log record at the INFO level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $dataReplace The log context
     * @return Boolean Whether the record has been processed
     */
    public function notice($message, array $dataReplace = [])
    {
        return static::$logger->notice(String::replace($message, $dataReplace), $dataReplace);
    }

    /**
     * Adds a log record at the WARNING level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $dataReplace The log context
     * @return Boolean Whether the record has been processed
     */
    public function warn($message, array $dataReplace = [])
    {
        return static::$logger->warn(String::replace($message, $dataReplace), $dataReplace);
    }

    /**
     * Adds a log record at the WARNING level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $dataReplace The log context
     * @return Boolean Whether the record has been processed
     */
    public function warning($message, array $dataReplace = [])
    {
        return static::$logger->warn(String::replace($message, $dataReplace), $dataReplace);
    }

    /**
     * Adds a log record at the ERROR level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $dataReplace The log context
     * @return Boolean Whether the record has been processed
     */
    public function err($message, array $dataReplace = [])
    {

        return static::$logger->err(String::replace($message, $dataReplace), $dataReplace);
    }

    /**
     * Adds a log record at the ERROR level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $dataReplace The log context
     * @return Boolean Whether the record has been processed
     */
    public function error($message, array $dataReplace = [])
    {
        return static::$logger->err(String::replace($message, $dataReplace), $dataReplace);
    }

    /**
     * Adds a log record at the CRITICAL level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $dataReplace The log context
     * @return Boolean Whether the record has been processed
     */
    public function crit($message, array $dataReplace = [])
    {
        return static::$logger->crit(String::replace($message, $dataReplace), $dataReplace);
    }

    /**
     * Adds a log record at the CRITICAL level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $dataReplace The log context
     * @return Boolean Whether the record has been processed
     */
    public function critical($message, array $dataReplace = [])
    {
        return static::$logger->crit(String::replace($message, $dataReplace), $dataReplace);
    }

    /**
     * Adds a log record at the ALERT level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $dataReplace The log context
     * @return Boolean Whether the record has been processed
     */
    public function alert($message, array $dataReplace = [])
    {
        return static::$logger->alert(String::replace($message, $dataReplace), $dataReplace);
    }

    /**
     * Adds a log record at the EMERGENCY level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $dataReplace The log context
     * @return Boolean Whether the record has been processed
     */
    public function emerg($message, array $dataReplace = [])
    {
        return static::$logger->emerg(String::replace($message, $dataReplace), $dataReplace);
    }

    /**
     * Adds a log record at the EMERGENCY level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function emergency($message, array $context = [])
    {
        return static::$logger->emerg(String::replace($message, $context), $context);
    }
}