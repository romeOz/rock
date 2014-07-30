<?php

namespace rock\cache\versioning;

use rock\cache\CacheInterface;
use rock\date\DateTime;

class Memcache extends \rock\cache\Memcache implements CacheInterface
{
    use VersioningTrait;

    /** @var  \Memcache */
    protected static $storage;

    /**
     * @inheritdoc
     */
    public function getTag($tag)
    {
        return static::$storage->get($this->prepareTag($tag));
    }

    /**
     * @inheritdoc
     */
    public function removeTag($tag)
    {
        return static::$storage->replace($this->prepareTag($tag), microtime(), MEMCACHE_COMPRESSED, 0);
    }


    protected function validTimestamp($key, array $tags = null)
    {
        if (empty($tags)) {
            return true;
        }
        foreach ($tags as $tag => $timestamp) {
            if ((!$tagTimestamp = static::$storage->get($tag)) ||
                DateTime::microtime($tagTimestamp) > DateTime::microtime($timestamp)
            ) {
                static::$storage->delete($key);

                return false;
            }
        }

        return true;
    }
}