<?php

namespace rock\log;


use Monolog\Formatter\LineFormatter;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use rock\base\Alias;
use rock\base\ObjectInterface;
use rock\base\ObjectTrait;
use rock\di\Container;
use rock\helpers\FileHelper;
use rock\helpers\StringHelper;

/**
 * @method static Log log(int $level, string $message, array $placeholders = [])
 * @method static Log debug(string $message, array $placeholders = [])
 * @method static Log info(string $message, array $placeholders = [])
 * @method static Log notice(string $message, array $placeholders = [])
 * @method static Log warn(string $message, array $placeholders = [])
 * @method static Log err(string $message, array $placeholders = [])
 * @method static Log crit(string $message, array $placeholders = [])
 * @method static Log alert(string $message, array $placeholders = [])
 * @method static Log emerg(string $message, array $placeholders = [])
 */
class Log implements LogInterface, ObjectInterface
{
    use ObjectTrait {
        ObjectTrait::__construct as parentConstruct;
        ObjectTrait::__call as parentCall;
    }

    /**
     * List handlers.
     * @var AbstractProcessingHandler[]
     */
    public $handlers = [];
    /** @var LoggerInterface */
    public $logger;

    public function __construct($config = [])
    {
        $this->parentConstruct($config);

        if (isset($this->logger)) {
            return;
        }

        $this->logger = new Logger('Rock');

        if (!$this->handlers) {
            $this->handlers = $this->defaultHandlers();
        }

        foreach ($this->handlers as $level => $ahndler) {
            $this->logger->pushHandler($ahndler);
        }
    }

    public function __call($name, $arguments)
    {
        if (method_exists($this, "{$name}Internal")) {
            return call_user_func_array([$this, "{$name}Internal"], $arguments);
        }

        return $this->parentCall($name, $arguments);
    }

    public static function __callStatic($name, $arguments)
    {
        /** @var static $self */
        $self = Container::load(static::className());
        return call_user_func_array([$self, $name], $arguments);
    }

    /**
     * Adds a log record at an arbitrary level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  int $level log level
     * @param  string $message log message
     * @param  array $placeholders placeholders for replacement
     * @return bool Whether the record has been processed
     */
    protected function logInternal($level, $message, array $placeholders = [])
    {
        return $this->logger->log($level, StringHelper::replace($message, $placeholders, false), $placeholders);
    }

    /**
     * Adds a log record at the `DEBUG` level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string $message log message
     * @param  array $placeholders placeholders for replacement
     * @return bool Whether the record has been processed
     */
    protected function debugInternal($message, array $placeholders = [])
    {
        return $this->logger->debug(StringHelper::replace($message, $placeholders, false), $placeholders);
    }

    /**
     * Adds a log record at the `INFO` level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string $message log message
     * @param  array $placeholders placeholders for replacement
     * @return bool Whether the record has been processed
     */
    protected function infoInternal($message, array $placeholders = [])
    {
        return $this->logger->info(StringHelper::replace($message, $placeholders, false), $placeholders);
    }

    /**
     * Adds a log record at the `NOTICE` level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string $message log message
     * @param  array $placeholders placeholders for replacement
     * @return bool Whether the record has been processed
     */
    protected function noticeInternal($message, array $placeholders = [])
    {
        return $this->logger->notice(StringHelper::replace($message, $placeholders, false), $placeholders);
    }

    /**
     * Adds a log record at the `WARNING` level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string $message log message
     * @param  array $placeholders placeholders for replacement
     * @return bool Whether the record has been processed
     */
    protected function warnInternal($message, array $placeholders = [])
    {
        return $this->logger->warn(StringHelper::replace($message, $placeholders, false), $placeholders);
    }

    /**
     * Adds a log record at the `ERROR` level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string $message log message
     * @param  array $placeholders placeholders for replacement
     * @return bool Whether the record has been processed
     */
    protected function errInternal($message, array $placeholders = [])
    {
        return $this->logger->err(StringHelper::replace($message, $placeholders, false), $placeholders);
    }

    /**
     * Adds a log record at the `CRITICAL` level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string $message log message
     * @param  array $placeholders placeholders for replacement
     * @return bool Whether the record has been processed
     */
    protected function critInternal($message, array $placeholders = [])
    {
        return $this->logger->crit(StringHelper::replace($message, $placeholders, false), $placeholders);
    }

    /**
     * Adds a log record at the `ALERT` level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string $message log message
     * @param  array $placeholders placeholders for replacement
     * @return bool Whether the record has been processed
     */
    protected function alertInternal($message, array $placeholders = [])
    {
        return $this->logger->alert(StringHelper::replace($message, $placeholders, false), $placeholders);
    }

    /**
     * Adds a log record at the `EMERGENCY` level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string $message log message
     * @param  array $placeholders placeholders for replacement
     * @return bool Whether the record has been processed
     */
    protected function emergInternal($message, array $placeholders = [])
    {
        return $this->logger->emerg(StringHelper::replace($message, $placeholders, false), $placeholders);
    }

    protected function defaultHandlers()
    {
        $path = $path = Alias::getAlias('@runtime/logs');
        FileHelper::createDirectory($path);
        $paths = [
            self::DEBUG => "{$path}/debug.log",
            self::INFO => "{$path}/info.log",
            self::NOTICE => "{$path}/error.log",
            self::WARNING => "{$path}/error.log",
            self::ERROR => "{$path}/error.log",
            self::CRITICAL => "{$path}/error.log",
            self::ALERT => "{$path}/error.log",
            self::EMERGENCY => "{$path}/error.log",
        ];
        $formatter = new LineFormatter("[%datetime%]\t%level_name%\t%extra.hash%\t%message%\t%extra.user_id%\t%extra.user_ip%\t%extra.user_agent%\n");
        $this->logger->pushProcessor(function ($record) {
            $record['extra']['hash'] = substr(md5($record['message']), -6);
            $record['extra']['user_agent'] = strip_tags($_SERVER['HTTP_USER_AGENT']);
            $record['extra']['user_ip'] = filter_input(INPUT_SERVER, 'REMOTE_ADDR', FILTER_VALIDATE_IP);
            $record['extra']['user_id'] = isset($_SESSION['user']['id']) ? $_SESSION['user']['id'] : 'NULL';
            return $record;
        });

        $handlers = [];
        foreach ($paths as $level => $path) {
            $handlers[$level] = (new StreamHandler($path, $level, false))->setFormatter($formatter);
        }
        return $handlers;
    }
}