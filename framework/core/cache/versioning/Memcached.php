<?php
namespace rock\cache\versioning;

use rock\cache\CacheInterface;
use rock\date\DateTime;

class Memcached extends \rock\cache\Memcached implements CacheInterface
{
    use VersioningTrait;

    /** @var  \Memcached */
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
        return static::$storage->replace($this->prepareTag($tag), microtime(), 0);
    }


    protected function validTimestamp($key, array $tagsByValue = [])
    {
        if (empty($tagsByValue)) {
            return true;
        }
        $tags = static::$storage->getMulti(array_keys($tagsByValue));
        foreach ($tagsByValue as $tag => $timestamp) {
            if (!isset($tags[$tag]) ||
                (isset($tags[$tag]) && DateTime::microtime($tags[$tag]) > DateTime::microtime($timestamp))
            ) {
                static::$storage->delete($key);

                return false;
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    protected function setTags($key, array $tags = [], &$value = null)
    {
        $value = ['value' => $value, 'tags' => []];
        if (empty($tags)) {
            return;
        }
        $timestamp = microtime();
        $tags = $this->prepareTags($tags);
        $data = static::$storage->getMulti($tags);
        foreach ($tags as $tag) {
            if (isset($data[$tag])) {
                $value['tags'][$tag] = $data[$tag];
                continue;
            }
            $this->provideLock($tag, $timestamp, 0);
            $value['tags'][$tag] = $timestamp;
        }
    }
}
