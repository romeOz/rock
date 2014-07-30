<?php

namespace rock\cache\versioning;

trait VersioningTrait
{
    /**
     * @inheritdoc
     */
    public function set($key, $value = null, $expire = 0, array $tags = null)
    {
        if (empty($key) || $this->enabled === false) {
            return false;
        }
        $key = $this->prepareKey($key);
        $this->setTags($key, $tags, $value);

        return $this->provideLock($key, $this->serialize($value), $expire);
    }

    /**
     * @inheritdoc
     */
    public function get($key, &$result = null)
    {
        if (($result = parent::get($key)) === false) {
            return false;
        }

        if (is_object($result)) {
            $result = (array)$result;
        }

        if ($this->validTimestamp($this->prepareKey($key), $result['tags']) === false) {
            return false;
        }

        $result['value'] = $result['value'] === '' ? null : $result['value'];
        return $result['value'];
    }

    /**
     * @inheritdoc
     */
    public function has($key)
    {
        return (bool)$this->get($key);
    }

    /**
     * @inheritdoc
     */
    public function increment($key, $offset = 1, $expire = 0)
    {
        $hash = $this->prepareKey($key);
        if ($this->get($key, $result) === false) {

            if ($this->provideLock($hash, $this->serialize(['value' => $offset, 'tags' => null]), $expire) === false) {
                return false;
            }
            return $offset;
        }

        if ($this->provideLock(
                 $hash,
                 $this->serialize(['value' => $result['value'] + $offset, 'tags' => $result['tags']]),
                 $expire) === false
        ) {
            return false;
        }
        return $result['value'] + $offset;
    }

    /**
     * @inheritdoc
     */
    public function decrement($key, $offset = 1, $expire = 0)
    {
        $hash = $this->prepareKey($key);
        if (($timestamp = $this->get($key, $result)) === false) {
            return false;
        }

        if ($this->provideLock(
                 $hash,
                 $this->serialize(['value' => $result['value'] - $offset, 'tags' => $result['tags']]),
                 $expire) === false
        ) {
            return false;
        }

        return $result['value'] - $offset;
    }

    /**
     * Set tags
     *
     * @param string $key
     * @param array  $tags
     * @param        $value
     */
    protected function setTags($key, array $tags = null, &$value = null)
    {
        $value = ['value' => $value, 'tags' => []];
        if (empty($tags)) {
            return;
        }
        $timestamp = microtime();
        foreach ($this->prepareTags($tags) as $tag) {
            if ($timestampTag = static::$storage->get($tag)) {
                $value['tags'][$tag] = $timestampTag;
                continue;
            }
            $this->provideLock($tag, $timestamp, 0);
            $value['tags'][$tag] = $timestamp;
        }
    }
} 