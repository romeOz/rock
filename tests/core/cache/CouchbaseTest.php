<?php
namespace rockunit\core\cache;

use rock\cache\CacheInterface;
use rock\cache\Couchbase;
use rock\cache\Exception;

/**
 * @group cache
 * @group couchbase
 */
class CouchbaseTest extends \PHPUnit_Framework_TestCase
{
    use  CommonTraitTest {
        CommonTraitTest::testGetAllKeys as parentTestGetAllKeys;
    }

    public static function flush()
    {
        (new Couchbase(['enabled' => true]))->flush();
    }

    public function init($serialize)
    {
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
     * @expectedException Exception
     */
    public function testGetAll(CacheInterface $cache)
    {
        $cache->getAll();
    }

    /**
     * @dataProvider providerCache
     * @expectedException Exception
     */
    public function testGetAllKeys(CacheInterface $cache)
    {
        $this->parentTestGetAllKeys($cache);
    }
} 