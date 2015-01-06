<?php

namespace rock\cache\versioning;

use rock\cache\CacheInterface;
use rock\date\DateTime;

class APC extends \rock\cache\APC implements CacheInterface
{
    use VersioningTrait;

    /**
     * @inheritdoc
     */
    public function getTag($tag)
    {
        return apc_fetch($this->prepareTag($tag));
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
            if ((!$tagTimestamp = apc_fetch($tag)) ||
                DateTime::microtime($tagTimestamp) > DateTime::microtime($timestamp)) {
                apc_delete($key);
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
        foreach ($this->prepareTags($tags) as $tag) {
            if ($timestampTag = apc_fetch($tag)) {
                $value['tags'][$tag] = $timestampTag;
                continue;
            }
            $this->provideLock($tag, $timestamp, 0);
            $value['tags'][$tag] = $timestamp;
        }
    }
}