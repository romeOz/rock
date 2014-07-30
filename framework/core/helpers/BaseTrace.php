<?php

namespace rock\helpers;

use rock\base\ClassName;
use rock\base\ComponentsTrait;


class BaseTrace
{
    use ClassName;

    const APP = 'app';
    const TOKEN_APP_RUNTIME = 'Runtime application';
    const DB = 'db';
    const DB_QUERY = 'db.query';
    const CACHE_GET = 'cache.get';
    const CACHE_DB = 'cache.db';

    /**
     * Exclude keys of token
     * @var array
     */
    public static $exclude = ['valid', 'cache', 'exception', 'count', 'time', 'increment'];

    protected static $traces = [];

    /**
     * @param string $category
     * @return mixed|null
     */
    public static function get($category)
    {
        return isset(static::$traces[$category]) ? static::$traces[$category] : null;
    }

    /**
     * Returns an iterator for traversing the session variables.
     * This method is required by the interface IteratorAggregate.
     *
     * @param string $category
     * @return \ArrayIterator an iterator for traversing the session variables.
     */
    public static function getIterator($category)
    {
        if (!isset(static::$traces[$category])) {
            return [];
        }
        return new \ArrayIterator(static::$traces[$category]);
    }

    /**
     * @param string $category
     * @return bool
     */
    public static function has($category)
    {
        return isset(static::$traces[$category]);
    }

    /**
     * @param string $category
     * @return int
     */
    public static function count($category = null)
    {
        return static::has($category) ? count(static::$traces[$category]) : count(static::$traces);
    }

    /**
     * @param string $category
     */
    public static function remove($category)
    {
        unset(static::$traces[$category]);
    }

    public static function removeAll()
    {
        static::$traces = [];
    }

    /**
     * @param string $category
     * @param mixed  $token
     */
    public static function trace($category, $token)
    {
        if (!isset(static::$traces[$category])) {
            static::$traces[$category] = [];
        }
        $hash = static::getHash($token);
        if (isset(static::$traces[$category][$hash])) {
            if (is_array($token)) {
                $token = array_merge(static::$traces[$category][$hash], $token);
                $token['count'] = isset(static::$traces[$category][$hash]['count']) ? ++static::$traces[$category][$hash]['count'] : 1;
            } else {
                $token = ['msg' => $token, 'count' => 1];
            }
        }
        static::$traces[$category][$hash] = $token;
    }


    public static function increment($category, $token)
    {
        $hash = static::getHash($token);
        if (isset(static::$traces[$category][$hash]) && is_array(static::$traces[$category][$hash])) {

            if (!isset(static::$traces[$category][$hash]['increment'])) {
                static::$traces[$category][$hash]['increment'] = 0;
            }
            ++static::$traces[$category][$hash]['increment'];
            return;
        }
        static::$traces[$category][$hash] = [];
        if (is_array($token)) {
            $token['increment'] = 1;
        } else {
            $token = ['msg' => $token, 'increment' => 1];
        }
        static::$traces[$category][$hash] = $token;
    }

    public static function decrement($category, $token)
    {
        $hash = static::getHash($token);
        if (isset(static::$traces[$category][$hash]) && is_array(static::$traces[$category][$hash])) {
            if (!isset(static::$traces[$category][$hash]['increment']) || static::$traces[$category][$hash]['increment'] <= 0) {
                static::$traces[$category][$hash]['increment'] = 1;
            }
            --static::$traces[$category][$hash]['increment'];
            return;
        }
        static::$traces[$category][$hash] = [];
        if (is_array($token)) {
            $token['increment'] = 0;
        } else {
            $token = ['msg' => $token, 'increment' => 0];
        }
        static::$traces[$category][$hash] = $token;
    }

    
    public static function beginProfile($category, $token)
    {
        $microtime = microtime(true);
        $hash = static::getHash($token);
        if (isset(static::$traces[$category][$hash]) && is_array(static::$traces[$category][$hash])) {
            static::$traces[$category][$hash]['time'] = $microtime;
            return;
        }
        static::$traces[$category][$hash] = [];

        if (is_array($token)) {
            $token['time'] = $microtime;
        } else {
            $token = ['msg' => $token, 'time' => $microtime];
        }
        static::$traces[$category][$hash] = $token;
    }

    public static function endProfile($category, $token)
    {
        $hash = static::getHash($token);
        if (isset(static::$traces[$category][$hash]['time'])) {
            static::$traces[$category][$hash]['time'] = microtime(true) - static::$traces[$category][$hash]['time'];
        }
    }

    protected static function getHash($token)
    {
        if (is_array($token)) {
            $token = ArrayHelper::prepareArray($token, [], static::$exclude);
            $token = serialize($token);
        }
        return crc32($token);
    }
}