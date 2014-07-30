<?php

namespace rock\filters;


use rock\exception\BaseException;

class RateLimiterException extends BaseException
{
    public function __construct($level = self::ERROR, $msg = null, array $dataReplace = [], \Exception $handler = null)
    {
        return parent::__construct($level, $msg, $dataReplace, $handler);
    }
}