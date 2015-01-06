<?php
namespace rockunit\core\cache;

use rock\cache\CacheInterface;
use rock\cache\Memcached;

/**
 * @group cache
 * @group memcached
 */
class MemcachedTest extends \PHPUnit_Framework_TestCase
{
    use CommonTraitTest;

    public static function flush()
    {
        (new Memcached())->flush();
    }

    public function init($serialize, $lock = true)
    {
        if (!class_exists('\Memcached')) {
            $this->markTestSkipped(
                'The \Memcached is not available.'
            );
        }
        return new Memcached(['serializer' => $serialize, 'lock' => $lock]);
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
        //        /** @var $this \PHPUnit_Framework_TestCase */
        //        $this->assertFalse($cache->status());
        $this->markTestSkipped(
            'Memcached::status() skipped. Changed behavior TravisCI.'
        );
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