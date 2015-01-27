<?php

namespace rock\cache;


use rock\base\BaseException;
use rock\events\EventsInterface;
use rock\log\Log;

class Redis implements CacheInterface, EventsInterface
{
    use CacheTrait {
        CacheTrait::__construct as parentConstruct;
    }

    public $host = 'localhost';
    public $port = 6379;

    /** @var  \Redis */
    public $storage;

    public function __construct($config = [])
    {
        $this->parentConstruct($config);
        $this->storage = new \Redis();
        $this->storage->connect($this->host, $this->port);
    }

    /**
     * {@inheritdoc}
     *
     * @return \Redis
     */
    public function getStorage()
    {
        return $this->storage;
    }

    /**
     * @inheritdoc
     */
    public function get($key)
    {
        $result = $this->provideGet($key);

        if ($result === '') {
            $result = null;
        }

        return $this->unserialize($result);
    }

    /**
     * @inheritdoc
     */
    public function set($key, $value = null, $expire = 0, array $tags = [])
    {
        if (empty($key)) {
            return false;
        }
        $key = $this->prepareKey($key);
        $this->setTags($key, $tags);

        return $this->provideLock($key, $this->serialize($value), $expire);
    }

    /**
     * @inheritdoc
     */
    public function add($key, $value = null, $expire = 0, array $tags = [])
    {
        if (empty($key)) {
            return false;
        }

        if ($this->exists($key)) {
            return false;
        }

        return $this->set($key, $value, $expire, $tags);
    }

    /**
     * @inheritdoc
     */
    public function touch($key, $expire = 0)
    {
        return $this->storage->expire($this->prepareKey($key), $expire);
    }

    /**
     * @inheritdoc
     */
    public function exists($key)
    {
        return $this->storage->exists($this->prepareKey($key));
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
            $expire > 0 ? $this->storage->setex($hash, $expire, 0) : $this->storage->set($hash, 0);
        }

        return $this->storage->incrBy($hash, $offset);
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
            $expire > 0 ? $this->storage->setex($hash, $expire, 0) : $this->storage->set($hash, 0);
        }

        return $this->storage->decrBy($hash, $offset);
    }

    /**
     * @inheritdoc
     */
    public function remove($key)
    {
        return (bool)$this->storage->delete($this->prepareKey($key));
    }

    /**
     * @inheritdoc
     */
    public function removeMulti(array $keys)
    {
        $keys = $this->prepareKeys($keys);
        $this->storage->delete($keys);
    }

    /**
     * @inheritdoc
     */
    public function getTag($tag)
    {
        return $this->storage->sMembers($this->prepareTag($tag)) ? : false;
    }

    /**
     * @inheritdoc
     */
    public function existsTag($tag)
    {
        return $this->storage->exists($this->prepareTag($tag));
    }

    /**
     * @inheritdoc
     */
    public function removeTag($tag)
    {
        $tag = $this->prepareTag($tag);
        if (!$value = $this->storage->sMembers($tag)) {
            return false;
        }
        $value[] = $tag;
        $this->storage->delete($value);
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getAllKeys($pattern = '*')
    {
        return $this->storage->keys($pattern);
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
    public function flush()
    {
        return $this->storage->flushDB();
    }

    /**
     * @inheritdoc
     */
    public function status()
    {
        return $this->storage->info();
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param int $expire
     * @return bool
     */
    protected function provideLock($key, $value, $expire)
    {
        if ($this->lock === false) {
            $expire > 0 ? $this->storage->setex($key, $expire, $value) : $this->storage->set($key, $value);
            return true;
        }
        if ($this->lock($key, $value)) {
            $expire > 0 ? $this->storage->setex($key, $expire, $value) : $this->storage->set($key, $value);
            $this->unlock($key);

            return true;
        }

        return false;
    }

    /**
     * Set lock.
     *
     * > Dog-pile" ("cache miss storm") and "race condition" effects.
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $max
     * @return bool
     */
    protected function lock($key, $value, $max = 15)
    {
        $iteration = 0;

        while (!$this->storage->setnx(self::LOCK_PREFIX . $key, $value)) {
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
     * Delete lock
     *
     * @param string $key
     */
    protected function unlock($key)
    {
        $this->storage->delete(self::LOCK_PREFIX . $key);
    }

    /**
     * Set tags
     *
     * @param string $key
     * @param array  $tags
     */
    protected function setTags($key, array $tags = null)
    {
        if (empty($tags)) {
            return;
        }

        foreach ($this->prepareTags($tags) as $tag) {
            $this->storage->sAdd($tag, $key);
        }
    }

    protected function getLock($key)
    {
        return $this->storage->get(self::LOCK_PREFIX . $key);
    }
}