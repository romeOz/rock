<?php

namespace rockunit\common;


use League\Flysystem\Adapter\Local;
use rock\base\Alias;
use rock\cache\CacheFile;
use rock\cache\CacheStub;
use rock\cookie\Cookie;
use rock\di\Container;
use rock\file\FileManager;
use rock\helpers\FileHelper;

use rockunit\core\session\mocks\SessionMock;

trait CommonTestTrait
{
    protected static $session = [];
    protected static $cookie = [];
    public static $post = [];

    /**
     * @param array|string $config config of session
     * @return SessionMock
     * @throws \rock\di\ContainerException
     */
    public static function getSession($config = 'session')
    {
        return Container::load($config);
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
        /** @var Cookie $cookie */
        $cookie = Container::load('cookie');
        $cookie->removeAll();
        static::getSession()->removeAll();
    }

    protected static function sessionDown()
    {
        static::$session = $_SESSION;
        static::$cookie = $_COOKIE;
        static::$post = $_POST;
    }

    protected static function getPathRuntime()
    {
        return Alias::getAlias('@runtime');
    }

    protected static function clearRuntime()
    {
        $runtime = static::getPathRuntime();
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
     * @param array $config
     * @return \rock\cache\CacheInterface
     * @throws \rock\di\ContainerException
     */
    protected static function getCache(array $config = [])
    {
        if (empty($config)) {
            $config = [
                'class' => CacheFile::className(),
                'adapter' => new FileManager([
                   'adapter' => new Local(Alias::getAlias('@rockunit/runtime/cache')),
                ])
            ];
        }
        Container::add('cache', $config);
        return Container::load('cache');
    }

    protected static function disableCache()
    {
        static::getCache(['class' => CacheStub::className()]);
    }
} 