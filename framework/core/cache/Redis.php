<?php

namespace rock\cache;


use rock\Rock;

class Redis implements CacheInterface
{
    use CacheTrait {
        CacheTrait::__construct as parentConstruct;
    }

    public $host = 'localhost';
    public $port = 6379;

    /** @var  \Redis */
    protected static $storage;

    public function __construct($config = [])
    {
        $this->parentConstruct($config);
        static::$storage = new \Redis();
        static::$storage->connect($this->host, $this->port);
    }

    /**
     * {@inheritdoc}
     *
     * @return \Redis
     */
    public function getStorage()
    {
        return static::$storage;
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
    public function set($key, $value = null, $expire = 0, array $tags = null)
    {
        if (empty($key) || $this->enabled === false) {
            return false;
        }
        $key = $this->prepareKey($key);
        $this->setTags($key, $tags);

        return $this->provideLock($key, $this->serialize($value), $expire);
    }

    /**
     * @inheritdoc
     */
    public function add($key, $value = null, $expire = 0, array $tags = null)
    {
        if (empty($key) || $this->enabled === false) {
            return false;
        }

        if ($this->has($key)) {
            return false;
        }

        return $this->set($key, $value, $expire, $tags);
    }

    /**
     * @inheritdoc
     */
    public function touch($key, $expire = 0)
    {
        return static::$storage->expire($this->prepareKey($key), $expire);
    }

    /**
     * @inheritdoc
     */
    public function has($key)
    {
        return static::$storage->exists($this->prepareKey($key));
    }

    /**
     * @inheritdoc
     */
    public function increment($key, $offset = 1, $expire = 0)
    {
        $hash = $this->prepareKey($key);
        if ($this->has($key) === false) {
            $expire > 0 ? static::$storage->setex($hash, $expire, 0) : static::$storage->set($hash, 0);
        }

        return static::$storage->incrBy($hash, $offset);
    }

    /**
     * @inheritdoc
     */
    public function decrement($key, $offset = 1, $expire = 0)
    {
        $hash = $this->prepareKey($key);
        if ($this->has($key) === false) {
            return false;
        }

        return static::$storage->decrBy($hash, $offset);
    }

    /**
     * @inheritdoc
     */
    public function remove($key)
    {
        return (bool)static::$storage->delete($this->prepareKey($key));
    }

    /**
     * @inheritdoc
     */
    public function removeMulti(array $keys)
    {
        $keys = array_map(
            function($value){
                return $this->prepareKey($value);
            },
            $keys
        );
        static::$storage->delete($keys);
    }

    /**
     * @inheritdoc
     */
    public function getTag($tag)
    {
        return static::$storage->sMembers($this->prepareTag($tag)) ? : false;
    }

    /**
     * @inheritdoc
     */
    public function existsTag($tag)
    {
        return static::$storage->exists($this->prepareTag($tag));
    }

    /**
     * @inheritdoc
     */
    public function removeTag($tag)
    {
        $tag = $this->prepareTag($tag);
        if (!$value = static::$storage->sMembers($tag)) {
            return false;
        }
        $value[] = $tag;
        static::$storage->delete($value);
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getAllKeys($pattern = '*')
    {
        return static::$storage->keys($pattern);
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
        return static::$storage->flushDB();
    }

    /**
     * @inheritdoc
     */
    public function status()
    {
        return static::$storage->info();
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param int $expire
     * @return bool
     */
    protected function provideLock($key, $value, $expire)
    {
        if ($this->lock($key, $value)) {
            $expire > 0 ? static::$storage->setex($key, $expire, $value) : static::$storage->set($key, $value);
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

        while (!static::$storage->setnx(self::LOCK_PREFIX . $key, $value)) {
            $iteration++;
            if ($iteration > $max) {
                Rock::error(CacheException::INVALID_SAVE, ['key' => $key]);
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
        static::$storage->delete(self::LOCK_PREFIX . $key);
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
            static::$storage->sAdd($tag, $key);
        }
    }

    protected function getLock($key)
    {
        return static::$storage->get(self::LOCK_PREFIX . $key);
    }
}