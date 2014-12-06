<?php
namespace rock\cache;

use rock\helpers\Json;
use rock\Rock;

/**
 * Memcached storage.
 *
 * if use expire "0", then time to live infinitely
 *
 * ```php
 * $cache = new Memcached;
 * $cache->set('key_1', 'foo', 0, ['tag_1']);
 * $cache->set('key_2', ['foo', 'bar'], 0, ['tag_1']);
 *
 * $cache->get('key_1'); //foo
 * $cache->get('key_2'); //['foo', 'bar']
 * ```
 *
 */
class Memcached implements CacheInterface
{
    use CacheTrait {
        CacheTrait::__construct as parentConstruct;
    }

    /** @var  \Memcached */
    protected static $storage;
    public $servers = [['localhost', 11211]];

    public function __construct($config = [])
    {
        $this->parentConstruct($config);
        static::$storage = new \Memcached();
        static::$storage->addServers($this->servers);
        static::$storage->setOption(\Memcached::OPT_COMPRESSION, true);
        if ($this->serializer !== self::SERIALIZE_JSON) {
            static::$storage->setOption(\Memcached::OPT_SERIALIZER, \Memcached::SERIALIZER_PHP);
        }
    }

    /**
     * Get current storage
     *
     * @return \Memcached
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

        if ($this->has($key)) {
            return false;
        }

        return $this->set($key, $value, $expire, $tags);
    }

    /**
     * @inheritdoc
     */
    public function has($key)
    {
        return (bool)static::$storage->get($this->prepareKey($key));
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
    public function increment($key, $offset = 1, $expire = 0)
    {
        $hash = $this->prepareKey($key);
        if (static::$storage->add($hash, $offset, $expire)) {
            return $offset;
        }

        return static::$storage->increment($hash, $offset);
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

        return static::$storage->decrement($hash, $offset);
    }

    /**
     * @inheritdoc
     */
    public function remove($key)
    {
        return static::$storage->delete($this->prepareKey($key));
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
        static::$storage->deleteMulti($keys);
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
    public function hasTag($tag)
    {
        return (bool)static::$storage->get($this->prepareTag($tag));
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
        static::$storage->deleteMulti($value);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function getAllKeys()
    {
        return static::$storage->getAllKeys();
    }

    /**
     * @inheritdoc
     */
    public function getAll()
    {
        return static::$storage->fetchAll();
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
     * Set tags
     *
     * @param string $key key of cache
     * @param array  $tags list of tags
     */
    protected function setTags($key, array $tags = null)
    {
        if (empty($tags)) {
            return;
        }

        foreach ($this->prepareTags($tags) as $tag) {
            if (($keys = static::$storage->get($tag)) !== false) {
                $keys = $this->unserialize($keys);
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

    protected function getLock($key)
    {
        return static::$storage->get(self::LOCK_PREFIX . $key);
    }

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
     * > Dog-pile" ("cache miss storm") and "race condition" effects
     *
     * @param string $key key of cache
     * @param mixed $value content of cache
     * @param int    $max max iteration
     * @return bool
     */
    protected function lock($key, $value, $max = 15)
    {
        $iteration = 0;

        while (!static::$storage->add(self::LOCK_PREFIX . $key, $value, 5)) {
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
     * @return bool|string[]
     */
    protected function unlock($key)
    {
        return static::$storage->delete(self::LOCK_PREFIX . $key);
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