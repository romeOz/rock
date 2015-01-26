<?php

namespace rock\cache;


use rock\components\ComponentsTrait;
use rock\helpers\Serialize;

trait CommonTrait
{
    use \rock\components\ComponentsTrait {
        ComponentsTrait::init as parentInit;
    }

    /**
     * Prefix of key
     * @var string
     */
    public $prefix;

    /**
     * @var int
     */
    public $hashKey = self::HASH_MD5;
    public $hashTag = 0;
    /**
     * Serializer
     * @var int
     */
    public $serializer = self::SERIALIZE_PHP;

    /**
     * Add prefix to key
     * @param string $prefix
     */
    public function addPrefix($prefix)
    {
        $this->prefix = "{$prefix}_";
    }

    /**
     * Get prepare key of cache
     *
     * @param string $key
     * @return string
     */
    public function prepareKey($key)
    {
        if ($this->hashKey & self::HASH_MD5) {
            return $this->prefix . md5($key);
        } elseif ($this->hashKey & self::HASH_SHA) {
            return $this->prefix . sha1($key);
        }
        return $this->prefix . $key;
    }

    /**
     * @param array $keys
     * @return string|null
     */
    protected function prepareKeys(array $keys = [])
    {
        if (empty($keys)) {
            return null;
        }

        return array_map(
            function($value){
                return $this->prepareKey($value);
            },
            $keys
        );
    }

    /**
     * @param array $tags tags
     * @return array|null
     */
    protected function prepareTags(array $tags = [])
    {
        if (empty($tags)) {
            return null;
        }
        $tags = array_unique($tags);
        sort($tags);

        return array_map(
            function($value){
                return $this->prepareTag($value);
            },
            $tags
        );
    }

    protected function prepareTag($tag)
    {
        if (empty($tag)) {
            return $tag;
        }

        if ($this->hashTag & self::HASH_MD5) {
            return self::TAG_PREFIX . md5($tag);
        } elseif ($this->hashTag & self::HASH_SHA) {
            return self::TAG_PREFIX . sha1($tag);
        }

        return self::TAG_PREFIX . $tag;
    }

    /**
     * Serialize value
     *
     * @param array $value
     * @return array|string
     */
    protected function serialize(array $value)
    {
        return Serialize::serialize($value, $this->serializer);
    }

    /**
     * Unserialize value
     *
     * @param $value
     * @return mixed
     */
    protected function unserialize($value)
    {
        return Serialize::unserialize($value, false);
    }

    /**
     * @inheritdoc
     */
    public function setMulti($values, $expire = 0, array $tags = [])
    {
        /** @var $this CacheTrait|CacheInterface */

        foreach ($values as $key => $value) {
            $this->set($key, $value, $expire, $tags);
        }
    }

    /**
     * @inheritdoc
     */
    public function getMulti(array $keys)
    {
        /** @var $this CacheTrait|CacheInterface */

        $result = [];
        foreach ($keys as $key) {
            if (($value = $this->get($key)) !== false) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function touchMulti(array $keys, $expire = 0)
    {
        /** @var $this CacheTrait|CacheInterface */

        $result = true;
        foreach ($keys as $key) {
            if (!$this->touch($key, $expire)) {
                $result = false;
            }
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getMultiTags(array $tags)
    {
        /** @var $this CacheTrait|CacheInterface */

        $result = [];
        foreach ($tags as $tag) {
            if ($value = $this->getTag($tag)) {
                $result[$tag] = $value;
            }
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function removeMultiTags(array $tags)
    {
        /** @var $this CacheTrait|CacheInterface */

        foreach ($tags as $tag) {
            $this->removeTag($tag);
        }
    }
} 