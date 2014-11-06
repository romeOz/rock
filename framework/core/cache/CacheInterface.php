<?php
namespace rock\cache;

use rock\helpers\SerializeInterface;


interface CacheInterface extends SerializeInterface
{
    const HASH_MD5 = 1;
    const HASH_SHA = 2;

    const LOCK_PREFIX  = 'lock_';
    const TAG_PREFIX   = 'tag_';

    /**
     * Get current storage
     *
*@throws CacheException
     * @return \Memcached|\Memcache|\Redis|\Couchbase
     */
    public function getStorage();

    /**
     * Get prepare key of cache
     *
     * @param string $key - key of cache
     * @return bool|string
     */
    public function prepareKey($key);

    /**
     * Add prefix to key
     * @param string $prefix
     */
    public function addPrefix($prefix);

    /**
     * Get cache
     *
     * @param string $key - key of cache
     * @return mixed|bool
     */
    public function get($key);

    /**
     * Get multiple cache
     *
     * @param array $keys - keys of cache
     * @return array
     */
    public function getMulti(array $keys);

    /**
     * Set cache
     *
     * @param string $key    - key - key of cache
     * @param mixed  $value  - content of cache
     * @param int    $expire - time to live (sec)
     * @param array  $tags   - tags
     * @return bool
     */
    public function set($key, $value = null, $expire = 0, array $tags = null);

    /**
     * Set multiple cache
     *
     * ```php
     * $cache = new Memcached;
     * $cache->setMulti(['key_1' => 'text_1', 'key_2' => 'text_2'], 0, ['tag_1', 'tag_2'])
     * ```
     *
     * @param array  $values
     * @param int    $expire - time to live (sec)
     * @param array  $tags   - names tags
     * @return bool
     */
    public function setMulti($values, $expire = 0, array $tags = null);

    /**
     * Add cache (return false, if already exists on the server)
     *
     * @param string $key    - key of cache
     * @param mixed  $value  - content of cache
     * @param int    $expire - time to live (sec)
     * @param array  $tags   - tags
     * @return bool
     */
    public function add($key, $value = null, $expire = 0, array $tags = null);

    /**
     * Has cache
     *
     * @param string $key - key of cache
     * @return bool
     */
    public function has($key);
    /**
     * Change ttl
     *
     * @param string    $key - key of cache
     * @param int       $expire - time to live (sec)
     * @return bool
     */
    public function touch($key, $expire = 0);

    /**
     * Change multiple ttl
     *
     * @param array    $keys - keys of cache
     * @param int       $expire - time to live (sec)
     */
    public function touchMulti(array $keys, $expire = 0);

    /**
     * Increment
     *
     * @param string    $key - key of cache
     * @param int $offset
     * @param int $expire - time to live (sec)
     * @return mixed
     */
    public function increment($key, $offset = 1, $expire = 0);

    /**
     * Decrement
     *
     * @param string    $key - key of cache
     * @param int $offset
     * @param int $expire - time to live (sec)
     * @return mixed
     */
    public function decrement($key, $offset = 1, $expire = 0);

    /**
     * Delete cache
     *
     * @param string $key - key - key of cache
     * @return bool
     */
    public function remove($key);

    /**
     * Delete multiple keys
     *
     * @param array $keys - keys of cache
     */
    public function removeMulti(array $keys);

    /**
     * Get tag
     *
     * @param string $tag - name of tag
     * @return mixed
     */
    public function getTag($tag);

    /**
     * Get tags
     *
     * @param array $tags - names of tags
     * @return mixed
     */
    public function getMultiTags(array $tags);

    /**
     * Has tag
     * @param string $tag - name of tag
     * @return bool
     */
    public function hasTag($tag);

    /**
     * Remove tag
     * @param $tag - name of tag
     * @return bool
     */
    public function removeTag($tag);

    /**
     * Remove multiple tags
     *
     * @param array $tags - names of tags
     */
    public function removeMultiTags(array $tags);

    /**
     * Get all keys cache
     * @return mixed
     */
    public function getAllKeys();

    /**
     * Get all cache
     * @return mixed
     */
    public function getAll();

    /**
     * Remove all cache
     * @return bool
     */
    public function flush();

    /**
     * Get status cache server
     *
*@throws CacheException
     * @return mixed
     */
    public function status();

    /**
     * Enabled caching
     */
    public function enabled();
}