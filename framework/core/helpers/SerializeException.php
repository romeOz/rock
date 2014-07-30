<?php

namespace rock\helpers;


use rock\exception\BaseException;

class SerializeException extends BaseException
{
    const NOT_SERIALIZE = 'Value does not serialization';
    public function __construct(
        $level = self::ERROR,
        $msg = null,
        array $dataReplace = [],
        \Exception $handler = null
    ) {
        return parent::__construct($level, $msg, $dataReplace, $handler);
    }
}