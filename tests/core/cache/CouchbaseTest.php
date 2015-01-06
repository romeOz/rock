<?php
namespace rockunit\core\cache;

use rock\cache\CacheInterface;
use rock\cache\Couchbase;

/**
 * @group couchbase
 * @group cache
 */
class CouchbaseTest extends \PHPUnit_Framework_TestCase
{
    use CommonTraitTest;

    public static function flush()
    {
        (new Couchbase())->flush();
    }

    public function init($serialize, $lock = true)
    {
        if (!class_exists('\Couchbase')) {
            $this->markTestSkipped(
                'The Couchbase is not available.'
            );
        }
        return new Couchbase(['serializer' => $serialize, 'lock' => $lock]);
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
        $cache->getAllKeys();
    }

    /**
     * @dataProvider providerCache
     */
    public function testDecrement(CacheInterface $cache)
    {
        /** @var $this \PHPUnit_Framework_TestCase */

        $this->assertEquals(5, $cache->increment('key7', 5), 'should be get: 5');
        $this->assertEquals(3, $cache->decrement('key7', 2), 'should be get: 3');
        $this->assertEquals(3, $cache->get('key7'), 'should be get: 3');

        $this->assertEquals(0, $cache->decrement('key17', 2), 'should be get: 0');
    }
} 