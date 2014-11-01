<?php
namespace rockunit\core\cache;

use rock\cache\CacheInterface;
use rock\cache\Memcached;

/**
 * @group cache
 * @group memcached
 */
class MemcachedTest extends CommonTraitTest
{
    public static function flush()
    {
        (new Memcached(['enabled' => true]))->flush();
    }

    public function init($serialize)
    {
        if (!class_exists('\Memcached')) {
            $this->markTestSkipped(
                'The \Memcached is not available.'
            );
        }
        return new Memcached(['enabled' => true, 'serializer' => $serialize]);
    }

    /**
     * @dataProvider providerCache
     */
    public function testGetStorage(CacheInterface $cache)
    {
        $this->assertTrue($cache->getStorage() instanceof \Memcached);
    }

    /**
     * @dataProvider providerCache
     */
    public function testGetAll(CacheInterface $cache)
    {
        $this->assertTrue($cache->set('key5', 'foo'), 'should be get: true');
        $this->assertTrue($cache->set('key6', ['bar', 'baz']), 'should be get: true');
        $this->assertFalse($cache->getAll());
    }

    /**
     * @dataProvider providerCache
     */
    public function testStatus(CacheInterface $cache)
    {
        /** @var $this \PHPUnit_Framework_TestCase */
        $this->assertFalse($cache->status());
    }
}