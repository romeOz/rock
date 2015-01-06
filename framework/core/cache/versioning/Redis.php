<?php

namespace rock\cache\versioning;

use rock\cache\CacheInterface;
use rock\date\DateTime;

class Redis extends \rock\cache\Redis implements CacheInterface
{
    use VersioningTrait;

    /** @var  \Redis */
    public $storage;


    /**
     * @inheritdoc
     */
    public function getTag($tag)
    {
        return $this->storage->get($this->prepareTag($tag));
    }

    /**
     * @inheritdoc
     */
    public function removeTag($tag)
    {
        if (!$this->existsTag($tag)) {
            return false;
        }

        return $this->provideLock($this->prepareTag($tag), microtime(), 0);
    }

    protected function validTimestamp($key, array $tagsByValue = [])
    {
        if (empty($tagsByValue)) {
            return true;
        }
        foreach ($tagsByValue as $tag => $timestamp) {
            if ((!$tagTimestamp = $this->storage->get($tag)) ||
                DateTime::microtime($tagTimestamp) > DateTime::microtime($timestamp)
            ) {
                $this->storage->delete($key);

                return false;
            }
        }

        return true;
    }
}