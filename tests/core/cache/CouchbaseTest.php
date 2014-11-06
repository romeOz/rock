<?php
namespace rockunit\core\cache;

use rock\cache\CacheInterface;
use rock\cache\Couchbase;

/**
 * @group couchbase
 * @group cache
 */
class CouchbaseTest extends CommonTraitTest
{
    public static function flush()
    {
        (new Couchbase(['enabled' => true]))->flush();
    }

    public function init($serialize)
    {
        if (!class_exists('\Couchbase')) {
            $this->markTestSkipped(
                'The Couchbase is not available.'
            );
        }
        return new Couchbase(['enabled' => true, 'serializer' => $serialize]);
    }

    /**
     * @dataProvider providerCache
     */
    public function testGetStorage(CacheInterface $cache)
    {
        $this->assertTrue($cache->getStorage() instanceof \Couchbase);
    }

    /**
     * @dataProvider providerCache
     * @expectedException \rock\cache\CacheException
     */
    public function testGetAll(CacheInterface $cache)
    {
        $cache->getAll();
    }

    /**
     * @dataProvider providerCache
     * @expectedException \rock\cache\CacheException
     */
    public function testGetAllKeys(CacheInterface $cache)
    {
        parent::testGetAllKeys($cache);
    }
} 