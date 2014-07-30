<?php

namespace rock\execute;


use rock\base\ObjectTrait;

abstract class Execute
{
    use ObjectTrait;

    /**
     * @param string $value
     * @return mixed
     */
    abstract public function get($value);
} 