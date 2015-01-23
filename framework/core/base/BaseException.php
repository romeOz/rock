<?php
namespace rock\base;

use rock\helpers\StringHelper;

class BaseException extends \Exception implements ExceptionInterface
{
    use ClassName;

    public static $format = '{{message}} {{class}}::{{method}} {{file}} on line {{line}}';

    /**
     * Constructor
     *
     * @param string     $msg         message
     * @param array      $placeholders placeholders for replacement
     * @param \Exception|null $handler     handler
     */
    public function __construct($msg, array $placeholders = [], \Exception $handler = null)
    {
        $msg = StringHelper::replace($msg, $placeholders);
        parent::__construct($msg, 0, $handler);
    }

    /**
     * Converts an exception into a PHP error.
     *
     * This method can be used to convert exceptions inside of methods like `__toString()`
     * to PHP errors because exceptions cannot be thrown inside of them.
     *
     * @param \Exception $exception the exception to convert to a PHP error.
     * @param bool       $asStack
     */
    public static function convertExceptionToError(\Exception $exception, $asStack = DEBUG)
    {
        trigger_error(static::convertExceptionToString($exception, $asStack), E_USER_ERROR);
    }

    /**
     * Converts an exception into a simple string.
     *
     * @param \Exception $exception the exception being converted
     * @param bool       $asStack
     * @return string the string representation of the exception.
     */
    public static function convertExceptionToString(\Exception $exception, $asStack = DEBUG)
    {
        $trace = $exception->getTrace();
        $placeholders = [
            'class' => isset($trace[0]['class']) ? $trace[0]['class'] : '',
            'method'=> isset($trace[0]['function']) ? $trace[0]['function'] : '',
            'message' => $asStack
                ? $exception->getMessage() . ' ' . $exception->getTraceAsString()
                : $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ];

        return StringHelper::replace(static::$format, $placeholders, false);
    }
}