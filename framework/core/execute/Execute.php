<?php

namespace rock\execute;


use rock\base\ObjectInterface;
use rock\base\ObjectTrait;

abstract class Execute implements ObjectInterface
{
    use ObjectTrait;

    /**
     * @param string $value
     * @return mixed
     */
    abstract public function get($value);
} 