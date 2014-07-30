<?php

namespace rockunit\common;


use rock\helpers\File;
use rock\Rock;

trait CommonTrait
{
    protected static $session = [];
    protected static $cookie = [];

    protected static function sessionUp()
    {
        $_SESSION = static::$session;
        $_COOKIE = static::$cookie;
        Rock::$app->cookie->removeAll();
        Rock::$app->session->removeAll();
    }

    protected static function sessionDown()
    {
        static::$session = $_SESSION;
        static::$cookie = $_COOKIE;
    }

    protected static function clearRuntime()
    {
        $runtime = Rock::getAlias('@runtime');
//        @rmdir("{$runtime}/cache");
//        @rmdir("{$runtime}/filesystem");
        File::deleteDirectory("{$runtime}/cache");
        File::deleteDirectory("{$runtime}/filesystem");
        @unlink("{$runtime}/cache.tmp");
        @unlink("{$runtime}/filesystem.tmp");
    }

    protected static function sort($value)
    {
        ksort($value);
        return $value;
    }
} 