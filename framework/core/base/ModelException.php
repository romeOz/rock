<?php

namespace rock\base;


use rock\exception\BaseException;

class ModelException extends BaseException
{
    public function __construct($level = self::ERROR, $msg = null, array $placeholders = [], \Exception $handler = null)
    {
        return parent::__construct($level, $msg, $placeholders, $handler);
    }
}