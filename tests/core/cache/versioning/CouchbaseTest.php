<?php
namespace rockunit\core\cache\versioning;

use rock\cache\versioning\Couchbase;
use rockunit\core\cache\CommonCacheTrait;
use rock\cache\CacheInterface;

/**
 * @group couchbase
 * @group cache
 */
class CouchbaseTest extends \PHPUnit_Framework_TestCase
{
    use CommonCacheTrait;

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
        $this->assertFalse($cache->exists('key7'), 'should be get: false');
    }

    /**
     * @dataProvider providerCache
     */
    public function testGetTag(CacheInterface $cache)
    {
        $this->assertTrue($cache->set('key25', ['one', 'two'], 0, ['tag_18', 'tag_17']));
        $this->assertTrue($cache->set('key26', 'three', 0, ['tag_18']));
        $this->assertInternalType('string', $cache->getTag('tag_18'), 'var should be type string');
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
    }

    /**
     * @dataProvider providerCache
     * @expectedException \rock\cache\CacheException
     */
    public function testGetAllKeys(CacheInterface $cache)
    {
        $cache->getAllKeys();
    }
} 