<?php

namespace rock\cache;

use rock\base\BaseException;
use rock\events\EventsInterface;
use rock\log\Log;;

class APC implements CacheInterface, EventsInterface
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
        if (empty($key)) {
            return false;
        }
        $key = $this->prepareKey($key);
        if (($result = $this->getLock($key)) === false) {
            if (($result = apc_fetch($key)) === false) {
                return false;
            }
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
    public function exists($key)
    {
        return (bool)apc_exists($this->prepareKey($key));
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
    public function increment($key, $offset = 1, $expire = 0, $create = true)
    {
        $hash = $this->prepareKey($key);
        if ($this->exists($key) === false) {
            if ($create === false) {
                return false;
            }
            apc_add($hash, 0, $expire);
        }

        return apc_inc($hash, $offset);
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
            apc_add($hash, 0, $expire);
        }

        return apc_dec($hash, $offset);
    }

    /**
     * @inheritdoc
     */
    public function remove($key)
    {
        return apc_delete($this->prepareKey($key));
    }

    /**
     * @inheritdoc
     */
    public function getTag($tag)
    {
        return $this->unserialize(apc_fetch($this->prepareTag($tag)));
    }

    /**
     * @inheritdoc
     */
    public function existsTag($tag)
    {
        return (bool)apc_exists($this->prepareTag($tag));
    }

    /**
     * @inheritdoc
     */
    public function removeTag($tag)
    {
        $tag = $this->prepareTag($tag);
        if (!$value = apc_fetch($tag)) {
            return false;
        }
        $value = $this->unserialize($value);
        $value[] = $tag;
        foreach ($value as $key) {
            apc_delete($key);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function getAllKeys()
    {
        if (!$result = iterator_to_array(new \APCIterator('user'))) {
            return null;
        }

        return array_keys($result);
    }

    /**
     * @inheritdoc
     */
    public function getAll()
    {
        return iterator_to_array(new \APCIterator('user'));
    }

    /**
     * @inheritdoc
     */
    public function flush()
    {
        return apc_clear_cache('user');
    }

    /**
     * @inheritdoc
     */
    public function status()
    {
        return apc_cache_info('user');
    }

    protected function getLock($key)
    {
        return apc_fetch(self::LOCK_PREFIX . $key);
    }

    /**
     * @param string $key
     * @param mixed  $value
     * @param int    $expire
     * @return bool
     */
    protected function provideLock($key, $value, $expire)
    {
        if ($this->lock === false) {
            apc_store($key, $value, $expire);
            return true;
        }
        if ($this->lock($key, $value)) {
            apc_store($key, $value, $expire);
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
        while (!apc_add(self::LOCK_PREFIX . $key, $value, 5)) {
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
     * @return bool|\string[]
     */
    protected function unlock($key)
    {
        return apc_delete(self::LOCK_PREFIX . $key);
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
            if (($value = apc_fetch($tag)) !== false) {
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