<?php

namespace rockunit\common;


use League\Flysystem\Adapter\Local;
use rock\cache\CacheFile;
use rock\cache\CacheStub;
use rock\file\FileManager;
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

    protected static function sortByKey($value)
    {
        ksort($value);
        return $value;
    }

    protected static function sort($value)
    {
        sort($value);
        return $value;
    }

    /**
     * @return \rock\cache\CacheInterface
     */
    protected static function getCache()
    {
        Rock::$app->di['cache'] = [
            'class' => CacheFile::className(),
            'adapter' => function (){
                return new FileManager([
                   'adapter' => function(){
                       return new Local(Rock::getAlias('@tests/runtime/cache'));
                   },
               ]);
            }
        ];
        return Rock::$app->cache;
    }


    protected static function disableCache()
    {
        Rock::$app->di['cache'] = [
            'class' => CacheStub::className()
        ];
    }
} 