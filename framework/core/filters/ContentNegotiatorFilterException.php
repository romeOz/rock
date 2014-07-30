<?php

namespace rock\filters;


use rock\exception\BaseException;

class ContentNegotiatorFilterException extends BaseException
{
    public function __construct($level = self::ERROR, $msg = null, array $dataReplace = [], \Exception $handler = null)
    {
        return parent::__construct($level, $msg, $dataReplace, $handler);
    }
}