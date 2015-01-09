<?php
namespace rock\mongodb;

use rock\cache\CacheException;
use rock\cache\CacheInterface;
use rock\cache\CacheTrait;
use rock\di\Container;

/**
 * Cache implements a cache application component by storing cached data in a MongoDB.
 *
 * By default, Cache stores session data in a MongoDB collection named 'cache' inside the default database.
 * This collection is better to be pre-created with fields 'id' and 'expire' indexed.
 * The collection name can be changed by setting {@see \rock\mongodb\Cache::$cacheCollection}.
 *
 * Please refer to {@see \rock\cache\CacheInterface} for common cache operations that are supported by Cache.
 *
 * The following example shows how you can configure the application to use Cache:
 *
 * ```php
 * 'cache' => [
 *     'class' => 'rock\mongodb\Cache',
 *     // 'db' => 'mymongodb',
 *     // 'cacheCollection' => 'my_cache',
 * ]
 * ```
 *
 */
class Cache implements CacheInterface
{
    use CacheTrait {
        CacheTrait::__construct as parentConstruct;
    }

    /**
     * @var Connection|string the MongoDB connection object or the application component ID of the MongoDB connection.
     * After the Cache object is created, if you want to change this property, you should only assign it
     * with a MongoDB connection object.
     */
    public $storage  = 'mongodb';
    /**
     * @var string|array the name of the MongoDB collection that stores the cache data.
     * Please refer to {@see \rock\mongodb\Connection::getCollection()} on how to specify this parameter.
     * This collection is better to be pre-created with fields 'id' and 'expire' indexed.
     */
    public $cacheCollection = 'cache';
    /**
     * @var integer the probability (parts per million) that garbage collection (GC) should be performed
     * when storing a piece of data in the cache. Defaults to 100, meaning 0.01% chance.
     * This number should be between 0 and 1000000. A value 0 meaning no GC will be performed at all.
     */
    public $gcProbability = 100;


    public function __construct($config = [])
    {
        $this->parentConstruct($config);
        if (is_string($this->storage)) {
            $this->storage = Container::load($this->storage);
        }

        $this->storage->getCollection($this->cacheCollection)->createIndex('id', ['unique' => true]);

    }

    /**
     * Get current storage
     *
     * @return Connection
     */
    public function getStorage()
    {
        return $this->storage;
    }

    /**
     * @inheritdoc
     */
    public function set($key, $value = null, $expire = 0, array $tags = [])
    {
        if (empty($key)) {
            return false;
        }

        $result = $this->updateInternal(
            $this->prepareKey($key),
            [
                'expire' => $expire > 0 ? $expire + time() : 0,
                'value' => $value,
                'tags' => $this->prepareTags($tags)
            ]
        );

        if ($result) {
            $this->gc();

            return true;
        } else {
            return $this->add($key, $value, $expire, $tags);
        }
    }

    protected function updateInternal($key, $data)
    {
        return $this->storage->getCollection($this->cacheCollection)
            ->update(['id' => $key], $data);
    }

    /**
     * @inheritdoc
     */
    public function add($key, $value = null, $expire = 0, array $tags = [])
    {
        if (empty($key)) {
            return false;
        }
        $this->gc();

        return $this->insertInternal(
            [
                'id' => $this->prepareKey($key),
                'expire' => $expire > 0 ? $expire + time() : 0,
                'value' => $value,
                'tags' => $this->prepareTags($tags)
            ]
        );
    }

    protected function insertInternal($data)
    {
        try {
            $this->storage->getCollection($this->cacheCollection)
                ->insert($data);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function get($key/*, &$result = null*/)
    {
        $key = $this->prepareKey($key);
        $query = new Query;
        $row = $query->select(['value'])
            ->from($this->cacheCollection)
            ->where([
                'id' => $key,
                '$or' => [
                    [
                        'expire' => 0
                    ],
                    [
                        'expire' => ['$gt' => time()]
                    ],
                ],
            ])
            ->one($this->storage);

        if (empty($row)) {
            return false;
        }

        return $row['value'] === '' ? null : $row['value'];
    }


    /**
     * @inheritdoc
     */
    public function exists($key)
    {
        $query = new Query;
        return $query
            ->from($this->cacheCollection)
            ->where([
                        'id' => $this->prepareKey($key),
                        '$or' => [
                            [
                                'expire' => 0
                            ],
                            [
                                'expire' => ['$gt' => time()]
                            ],
                        ],
                    ])
            ->exists($this->storage);
    }

    /**
     * @inheritdoc
     */
    public function increment($key, $offset = 1, $expire = 0, $create = true)
    {
        $condition = [
            'id' => $this->prepareKey($key),
            '$or' => [
                [
                    'expire' => 0
                ],
                [
                    'expire' => ['$gt' => time()]
                ],
            ]
        ];
        $update = [
            //'expire' => $expire > 0 ? $expire + time() : 0,
            '$inc' => ['value' => $offset],
            '$set' => ['expire' => $expire > 0 ? $expire + time() : 0]
        ];
        $fields = ['value' => 1];
        $options = $create === true ? ['new' => true, 'upsert' => true] : ['new' => true];
        if (!$row  = $this->storage->getCollection($this->cacheCollection)
            ->findAndModify($condition, $update, $fields, $options)) {
            return false;
        }

        return $row['value'];
    }

    /**
     * @inheritdoc
     */
    public function decrement($key, $offset = 1, $expire = 0, $create = true)
    {
        $condition = [
            'id' => $this->prepareKey($key),
            '$or' => [
                [
                    'expire' => 0
                ],
                [
                    'expire' => ['$gt' => time()]
                ],
            ]
        ];
        $update = [
            '$inc' => ['value' => -1 * $offset],
            '$set' => ['expire' => $expire > 0 ? $expire + time() : 0]
        ];
        $fields = ['value' => 1];
        $options = $create === true ? ['new' => true, 'upsert' => true] : ['new' => true];
        if (!$row  = $this->storage->getCollection($this->cacheCollection)
            ->findAndModify($condition, $update, $fields, $options)) {
            return false;
        }

        return $row['value'];
    }


    /**
     * @inheritdoc
     */
    public function getTag($tag)
    {
        throw new CacheException(CacheException::UNKNOWN_METHOD, ['method' => __METHOD__]);
    }

    /**
     * @inheritdoc
     */
    public function removeTag($tag)
    {
        return (bool)$this->storage->getCollection($this->cacheCollection)
            ->remove(['tags' => $this->prepareTag($tag)]);
    }

    /**
     * @inheritdoc
     */
    public function getMulti(array $keys)
    {
        $keys = $this->prepareKeys($keys);
        $query = new Query;
        $rows = $query->select(['id', 'value'])
            ->from($this->cacheCollection)
            ->where([
                        'id' => ['$in' => $keys],
                        '$or' => [
                            [
                                'expire' => 0
                            ],
                            [
                                'expire' => ['$gt' => time()]
                            ],
                        ],
                    ])
            ->indexBy('id')
            ->all($this->storage);

        if (empty($rows)) {
            return [];
        }
        $result = [];
        foreach ($rows as $key => $value) {
            $result[$key] = $value['value'] === '' ? null : $value['value'];
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function touch($key, $expire = 0)
    {
        $result = $this->updateInternal(
            $this->prepareKey($key),
            [
                'expire' => $expire > 0 ? $expire + time() : 0,
            ]
        );

        if ($result) {
            $this->gc();

            return true;
        }
        return false;
    }

    /**
     * @inheritdoc
     */
    public function touchMulti(array $keys, $expire = 0)
    {
        return (bool)$this->storage->getCollection($this->cacheCollection)
            ->update(
                ['id' => ['$in' => $this->prepareKeys($keys)],
                    '$or' => [
                        [
                            'expire' => 0
                        ],
                        [
                            'expire' => ['$gt' => time()]
                        ],
                    ],
                ],
                ['expire' => $expire > 0 ? $expire + time() : 0]
            );
    }

    /**
     * @inheritdoc
     */
    public function remove($key)
    {
        return (bool)$this->storage->getCollection($this->cacheCollection)
            ->remove(['id' => $this->prepareKey($key)]);
    }

    /**
     * @inheritdoc
     */
    public function removeMulti(array $keys)
    {
        $this->storage->getCollection($this->cacheCollection)
            ->remove(['id' => ['$in' => $this->prepareKeys($keys)]]);
    }

    /**
     * @inheritdoc
     */
    public function getMultiTags(array $tags)
    {
        throw new CacheException(CacheException::UNKNOWN_METHOD, ['method' => __METHOD__]);
    }

    /**
     * @inheritdoc
     */
    public function existsTag($tag)
    {
        throw new CacheException(CacheException::UNKNOWN_METHOD, ['method' => __METHOD__]);
    }

    /**
     * @inheritdoc
     */
    public function removeMultiTags(array $tags)
    {
        $this->storage->getCollection($this->cacheCollection)
            ->remove(['tags' => ['$in' => $this->prepareTags($tags)]]);
    }

    /**
     * @inheritdoc
     */
    public function getAllKeys($limit = 1000)
    {
        return array_keys($this->getAll($limit));
    }

    /**
     * @inheritdoc
     */
    public function getAll($limit = 1000)
    {
        $cursor = $this->storage->getCollection($this->cacheCollection)->find([], ['id', 'value'])->limit($limit);
        $result = [];
        foreach ($cursor as $data) {
            $result[$data['id']] = $data['value'];
        }

        return $result;
    }


    /**
     * @inheritdoc
     */
    public function flush()
    {
        $this->storage->getCollection($this->cacheCollection)
            ->remove();

        return true;
    }

    /**
     * @inheritdoc
     */
    public function status()
    {
        throw new CacheException(CacheException::UNKNOWN_METHOD, ['method' => __METHOD__]);
    }

    /**
     * Removes the expired data values.
     *
     * @param boolean $force whether to enforce the garbage collection regardless of {@see \rock\mongodb\Cache::$gcProbability}.
     * Defaults to false, meaning the actual deletion happens with the probability as specified by {@see \rock\mongodb\Cache::$gcProbability}.
     */
    public function gc($force = false)
    {
        if ($force || mt_rand(0, 1000000) < $this->gcProbability) {
            $this->storage->getCollection($this->cacheCollection)
                ->remove([
                             'expire' => [
                                 '$gt' => 0,
                                 '$lt' => time(),
                             ]
                         ]);
        }
    }
}
