<?php

namespace rock\cache;

use rock\helpers\Json;
use rock\Rock;

class Couchbase implements CacheInterface
{
    use CacheTrait {
        CacheTrait::__construct as parentConstruct;
    }

    /**
     * @var string|array
     */
    public $host = 'localhost:8091';
    /** @var  string */
    public $user = '';
    /** @var  string */
    public $password = '';
    /** @var string  */
    public $bucket = 'default';

    /** @var  \Couchbase */
    protected static $storage;

    public function __construct(array $config = [])
    {
        $this->parentConstruct($config);
        static::$storage = new \Couchbase($this->host, $this->user, $this->password, $this->bucket);
        if ($this->serializer !== self::SERIALIZE_JSON) {
            static::$storage->setOption(COUCHBASE_OPT_SERIALIZER, COUCHBASE_SERIALIZER_PHP);
        }
    }

    /**
     * @inheritdoc
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
        return $this->unserialize($this->provideGet($key));
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
        if (($value = $this->get($key)) === false) {
            return false;
        }

        return $this->set($key, $value, $expire);
    }

    /**
     * @inheritdoc
     */
    public function exists($key)
    {
        $key = $this->prepareKey($key);
        if (static::$storage->add($key, true)) {
            static::$storage->delete($key);
            return false;
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function increment($key, $offset = 1, $expire = 0)
    {
        $hash = $this->prepareKey($key);
        if (static::$storage->add($hash, $offset, $expire)) {
            return $offset;
        }

        return static::$storage->increment($hash, $offset, false, $expire);
    }

    /**
     * @inheritdoc
     */
    public function decrement($key, $offset = 1, $expire = 0)
    {
        $hash = $this->prepareKey($key);
        if ($this->exists($key) === false) {
            return false;
        }

        return static::$storage->decrement($hash, $offset, $expire);
    }

    /**
     * @inheritdoc
     */
    public function remove($key)
    {
        return is_string(static::$storage->delete($this->prepareKey($key)));
    }

    /**
     * @inheritdoc
     */
    public function getTag($tag)
    {
        return $this->unserialize(static::$storage->get($this->prepareTag($tag)));
    }

    /**
     * @inheritdoc
     */
    public function existsTag($tag)
    {
        $tag = $this->prepareTag($tag);
        if (static::$storage->add($tag, true)) {
            static::$storage->delete($tag);
            return false;
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function removeTag($tag)
    {
        $tag = $this->prepareTag($tag);
        if (!$value = static::$storage->get($tag)) {
            return false;
        }
        $value = $this->unserialize($value);
        $value[] = $tag;
        foreach ($value as $key) {
            if (!empty($key)) {
                static::$storage->delete($key);
            }
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
    public function flush()
    {
        return static::$storage->flush();
    }

    /**
     * @inheritdoc
     */
    public function status()
    {
        return static::$storage->getStats();
    }

    /**
     * Set tags.
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
            if ($keys = static::$storage->get($tag)) {
                $keys = $this->unserialize($keys);
                if (is_object($keys)) {
                    $keys = (array)$keys;
                }
                if (in_array($key, $keys, true)) {
                    continue;
                }
                $keys[] = $key;
                $this->provideLock($tag, $this->serialize($keys), 0);
                continue;
            }
            $this->provideLock($tag, $this->serialize((array)$key), 0);
        }
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
            static::$storage->set($key, $value, $expire);
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

        while (!(bool)static::$storage->add(self::LOCK_PREFIX . $key, $value, 5)) {
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
     * @return bool|\string[]
     */
    protected function unlock($key)
    {
        return static::$storage->delete(self::LOCK_PREFIX . $key);
    }

    protected function getLock($key)
    {
        return static::$storage->get(self::LOCK_PREFIX . $key);
    }


    protected function serialize($value)
    {
        if (!is_array($value)) {
            return $value;
        }

        if ($this->serializer & self::SERIALIZE_JSON) {
            return Json::encode($value);
        }

        return $value;
    }
}