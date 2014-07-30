<?php

namespace rock\helpers;


use Jeremeamia\SuperClosure\SerializableClosure;

class BaseClosure
{
    public static function serialize(\Closure $callback)
    {
        return serialize(new SerializableClosure($callback));
    }
} 