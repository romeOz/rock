<?php

namespace rockunit\mocks;


use rock\cookie\Cookie;
use rock\helpers\Sanitize;
use rock\helpers\Serialize;

class CookieMock extends Cookie
{

    public function remove($name)
    {
        unset($_COOKIE[$name], static::$data[$name]);
    }

    public function add($name, $value)
    {
        $value = Sanitize::sanitize($value);
        if (is_array($value)) {
            $value = Serialize::serialize($value, $this->serializator);
        }
        $_COOKIE[$name] = $value;
    }
} 