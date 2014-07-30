<?php
namespace rock\cache;

use rock\exception\BaseException;

/**
 * Exception "Exception"
 *
 * @package rock\cache
 */
class Exception extends BaseException
{
    const NOT_UNIQUE  = 'keys must be unique: {data}';
    const INVALID_SAVE = 'cache invalid save by key: {key}';

    public function __construct(
        $level = self::ERROR,
        $msg = null,
        array $dataReplace = [],
        \Exception $handler = null
    ) {
        return parent::__construct($level, $msg, $dataReplace, $handler);
    }
}