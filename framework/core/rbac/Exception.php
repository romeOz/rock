<?php

namespace rock\rbac;


use rock\exception\BaseException;

class Exception extends BaseException
{
    const UNKNOWN_CHILD = 'Unknown child: {name}';
    const UNKNOWN_TYPE = 'Unknown type: {name}';
    const UNKNOWN_ROLE = 'Unknown role: {name}';
    const UNKNOWN_PERMISSION = 'Unknown permission: {name}';
    const NOT_DATA_PARAMS = 'Does not data params of item';

    public function __construct(
        $level = self::ERROR,
        $msg = null,
        array $dataReplace = [],
        \Exception $handler = null
    ) {
        return parent::__construct($level, $msg, $dataReplace, $handler);
    }
}