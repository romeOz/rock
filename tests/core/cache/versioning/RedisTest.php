<?php
namespace rockunit\core\cache\versioning;


use rock\cache\CacheInterface;
use rock\cache\CacheException;
use rock\cache\versioning\Redis;
use rockunit\core\cache\CommonTraitTest;

/**
 * @group cache
 * @group redis
 */
class RedisTest extends CommonTraitTest
{
    public static function flush()
    {
        (new Redis(['enabled' => true]))->flush();
    }

    public function init($serialize)
    {
        if (!class_exists('\Redis')) {
            $this->markTestSkipped(
                'The \Redis is not available.'
            );
        }
        return new Redis(['enabled' => true, 'serializer' => $serialize]);
    }

    /**
     * @dataProvider providerCache
     */
    public function testGetStorage(CacheInterface $cache)
    {
        $this->assertTrue($cache->getStorage() instanceof \Redis);
    }

    /**
     * @dataProvider providerCache
     * @expectedException CacheException
     */
    public function testGetAll(CacheInterface $cache)
    {
        $cache->getAll();
    }

    /**
     * @dataProvider providerCache
     */
    public function testTtlDecrement(CacheInterface $cache)
    {
        $this->assertEquals($cache->increment('key7', 5), 5, 'should be get: 5');
        $this->assertEquals($cache->decrement('key7', 2, 1), 3, 'should be get: 3');
        sleep(2);
        $this->assertFalse($cache->get('key7'), 'should be get: false');
    }

    /**
     * @dataProvider providerCache
     */
    public function testHasTtlDecrement(CacheInterface $cache)
    {
        $this->assertEquals($cache->increment('key7', 5), 5, 'should be get: 5');
        $this->assertEquals($cache->decrement('key7', 2, 1), 3, 'should be get: 3');
        sleep(2);
        $this->assertFalse($cache->has('key7'), 'should be get: false');
    }

    /**
     * @dataProvider providerCache
     */
    public function testGetTag(CacheInterface $cache)
    {
        $this->assertTrue($cache->set('key1', ['one', 'two'], 0, ['foo', 'bar']));
        $this->assertTrue($cache->set('key2', 'three', 0, ['foo']));
        $this->assertInternalType('string', $cache->getTag('foo'), 'var should be type string');
    }


    /**
     * @dataProvider providerCache
     */
    public function testRemoveTag(CacheInterface $cache)
    {
        $this->assertTrue($cache->set('key1', ['one', 'two'], 0, ['foo', 'bar']));
        $this->assertTrue($cache->set('key2', 'three', 0, ['foo']));
        $timestamp = $cache->getTag('bar');
        $this->assertTrue($cache->removeTag('bar'), 'tag "bar" does not remove');
        $this->assertFalse($cache->get('key1'), 'should be get: false');
        $this->assertNotEquals($cache->getTag('bar'), $timestamp, 'timestamps does not equals');
        $this->assertTrue($cache->remove('key2'));
        $this->assertFalse($cache->get('key2'), 'should be get: false');
        $expected = $cache->getAllKeys();
        if ($expected !== false) {
            $actual = [CacheInterface::TAG_PREFIX . 'foo', CacheInterface::TAG_PREFIX . 'bar'];
            sort($expected);
            sort($actual);
            $this->assertEquals($expected, $actual, 'should be get: ' . json_encode($actual));
        }
    }
}
 