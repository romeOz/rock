<?php

namespace rock\cache;


use rock\events\EventsInterface;

class CacheStub implements CacheInterface, EventsInterface
{
    use CacheTrait;

    /**
     * @inheritdoc
     */
    public function getStorage()
    {
        throw new CacheException(CacheException::UNKNOWN_METHOD, ['method' => __METHOD__]);
    }

    /**
     * @inheritdoc
     */
    public function get($key)
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function getMulti(array $keys)
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function set($key, $value = null, $expire = 0, array $tags = [])
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function setMulti($values, $expire = 0, array $tags = [])
    {
    }

    /**
     * @inheritdoc
     */
    public function add($key, $value = null, $expire = 0, array $tags = [])
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function exists($key)
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function touch($key, $expire = 0)
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function touchMulti(array $keys, $expire = 0)
    {
    }

    /**
     * @inheritdoc
     */
    public function increment($key, $offset = 1, $expire = 0, $create = true)
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function decrement($key, $offset = 1, $expire = 0, $create = true)
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function remove($key)
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function removeMulti(array $keys)
    {
    }

    /**
     * @inheritdoc
     */
    public function getTag($tag)
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function getMultiTags(array $tags)
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function existsTag($tag)
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function removeTag($tag)
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function removeMultiTags(array $tags)
    {
    }

    /**
     * @inheritdoc
     */
    public function getAllKeys()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAll()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function flush()
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function status()
    {
        return null;
    }
} 