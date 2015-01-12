<?php

namespace rockunit\common;


use League\Flysystem\Adapter\Local;
use rock\cache\CacheFile;
use rock\cache\CacheStub;
use rock\file\FileManager;
use rock\helpers\FileHelper;
use rock\Rock;
use rockunit\core\session\mocks\SessionMock;

trait CommonTestTrait
{
    protected static $session = [];
    protected static $cookie = [];
    public static $post = [];

    /**
     * @return SessionMock
     */
    public static function getSession()
    {
        Rock::$app->di['session'] = [
            'class' => SessionMock::className(),
            //'singleton' => true
        ];

        return Rock::$app->session;
    }

    public static function activeSession($active = true)
    {
        SessionMock::$isActive = $active;
    }


    protected static function sessionUp()
    {
        $_SESSION = static::$session;
        $_COOKIE = static::$cookie;
        $_POST = static::$post;
        Rock::$app->cookie->removeAll();
        static::getSession()->removeAll();
    }

    protected static function sessionDown()
    {
        static::$session = $_SESSION;
        static::$cookie = $_COOKIE;
        static::$post = $_POST;
    }

    protected static function clearRuntime()
    {
        $runtime = Rock::getAlias('@runtime');
//        @rmdir("{$runtime}/cache");
//        @rmdir("{$runtime}/filesystem");
        FileHelper::deleteDirectory("{$runtime}/cache");
        FileHelper::deleteDirectory("{$runtime}/filesystem");
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