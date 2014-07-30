<?php

namespace rock\filters;


use Exception;
use rock\exception\BaseException;

class VerbsFilterException extends BaseException
{
    public function __construct($level = self::ERROR, $msg = null, array $dataReplace = [], \Exception $handler = null)
    {
        return parent::__construct($level, $msg, $dataReplace, $handler);
    }
}