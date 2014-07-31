<?php
namespace rockunit\core\cache;

use rock\cache\CacheInterface;
use rock\cache\Exception;
use rock\cache\Memcache;

/**
 * @group cache
 * @group memcache
 */
class MemcacheTest extends CommonTraitTest
{
    public static function flush()
    {
        (new Memcache(['enabled' => true]))->flush();
    }

    public function init($serialize)
    {
        return new Memcache(['enabled' => true, 'serializer' => $serialize]);
    }

    /**
     * @dataProvider providerCache
     */
    public function testGetStorage(CacheInterface $cache)
    {
        $this->assertTrue($cache->getStorage() instanceof \Memcache);
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
        $cache->getAllKeys();
    }
}