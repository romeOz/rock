<?php

namespace rock\cache;

use rock\base\BaseException;
use rock\log\Log;

class Memcache extends Memcached
{
    /** @var  \Memcache */
    public $storage;

    public function __construct($config = [])
    {
        parent::__construct($config);
        $this->storage = new \Memcache();
        foreach ($this->servers as $server) {
            if (!isset($server[1])) {
                $server[1] = 11211;
            }
            if (!isset($server[2])) {
                $server[2] = true;
            }
            if (!isset($server[3])) {
                $server[3] = 1;
            }
            list($host, $port, $persistent, $weight) = $server;
            $this->storage->addserver($host, $port, $persistent, $weight);
        }
    }

    /**
     * @inheritdoc
     */
    public function increment($key, $offset = 1, $expire = 0, $create = true)
    {
        $hash = $this->prepareKey($key);
        if ($this->exists($key) === false) {
            if ($create === false) {
                return false;
            }
            $this->storage->add($hash, 0, MEMCACHE_COMPRESSED, $expire);
        }

        return $this->storage->increment($hash, $offset);
    }

    /**
     * @inheritdoc
     */
    public function decrement($key, $offset = 1, $expire = 0, $create = true)
    {
        $hash = $this->prepareKey($key);
        if ($this->exists($key) === false) {
            if ($create === false) {
                return false;
            }
            $this->storage->add($hash, 0, MEMCACHE_COMPRESSED, $expire);
        }

        return $this->storage->decrement($hash, $offset);
    }

    /**
     * @inheritdoc
     */
    public function removeMulti(array $keys)
    {
        foreach ($keys as $key) {
            $this->remove($key);
        }
    }

    /**
     * @inheritdoc
     */
    public function removeTag($tag)
    {
        $tag = $this->prepareTag($tag);
        if (($value = $this->storage->get($tag)) === false) {
            return false;
        }
        $value = $this->unserialize($value);
        $value[] = $tag;
        foreach ($value as $key) {
            $this->storage->delete($key);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function getAllKeys()
    {
        throw new CacheException(CacheException::UNKNOWN_METHOD, ['method' => __METHOD__]);
    }

    /**
     * @inheritdoc
     */
    public function getAll()
    {
        throw new CacheException(CacheException::UNKNOWN_METHOD, ['method' => __METHOD__]);
    }

    /**
     * @inheritdoc
     */
    protected function provideLock($key, $value, $expire, &$count = 0)
    {
        if ($this->lock === false) {
            $this->storage->set($key, $value, MEMCACHE_COMPRESSED, $expire);
            return true;
        }
        if ($this->lock($key, $value)) {
            $this->storage->set($key, $value, MEMCACHE_COMPRESSED, $expire);
            $this->unlock($key);
            return true;
        }

        return false;
    }


    /**
     * @inheritdoc
     */
    protected function lock($key, $value, $max = 15)
    {
        $iteration = 0;

        while (!$this->storage->add(self::LOCK_PREFIX . $key, $value, MEMCACHE_COMPRESSED, 5)) {
            $iteration++;
            if ($iteration > $max) {
                if (class_exists('\rock\log\Log')) {
                    $message = BaseException::convertExceptionToString(new CacheException(CacheException::INVALID_SAVE, ['key' => $key]));
                    Log::err($message);
                }
                return false;
            }
            usleep(1000);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    protected function setTags($key, array $tags = [])
    {
        if (empty($tags)) {
            return;
        }

        foreach ($this->prepareTags($tags) as $tag) {
            if (($value = $this->storage->get($tag)) !== false) {
                $value = $this->unserialize($value);
                if (in_array($key, $value, true)) {
                    continue;
                }
                $value[] = $key;
                $this->provideLock($tag, $this->serialize($value), 0);
                continue;
            }

            $this->provideLock($tag, $this->serialize((array)$key), 0);
        }
    }
}