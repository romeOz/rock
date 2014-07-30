<?php

namespace rock\i18n;


use rock\exception\BaseException;

class Exception extends BaseException
{
    const UNKNOWN_I18N = 'unknown i18n: {name}';

    public function __construct(
        $level = self::ERROR,
        $msg = null,
        array $dataReplace = [],
        \Exception $handler = null
    ) {
        return parent::__construct($level, $msg, $dataReplace, $handler);
    }
}