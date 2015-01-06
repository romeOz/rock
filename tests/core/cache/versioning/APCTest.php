<?php
namespace rockunit\core\cache\versioning;


use rock\cache\CacheInterface;
use rock\cache\versioning\APC;
use rockunit\core\cache\CommonTraitTest;

/**
 * @group cache
 * @group apc
 */
class APCTest extends \PHPUnit_Framework_TestCase
{
    use CommonTraitTest;

    public static function flush()
    {
        (new APC())->flush();
    }

    public function init($serialize)
    {
        if (!extension_loaded('apc')) {
            $this->markTestSkipped(
                'The APC is not available.'
            );
        }
        return new APC(['serializer' => $serialize]);
    }

    /**
     * @dataProvider providerCache
     * @expectedException \rock\cache\CacheException
     */
    public function testGetStorage(CacheInterface $cache)
    {
        $cache->getStorage();
    }

    /**
     * @dataProvider providerCache
     */
    public function testTtl(CacheInterface $cache)
    {
        $this->markTestSkipped('Skipping: ' . __METHOD__);
    }

    /**
     * @dataProvider providerCache
     */
    public function testHasByTouchFalse(CacheInterface $cache)
    {
        $this->markTestSkipped('Skipping: ' . __METHOD__);
    }

    /**
     * @dataProvider providerCache
     */
    public function testTouch(CacheInterface $cache)
    {
        $this->markTestSkipped('Skipping: ' . __METHOD__);
    }

    /**
     * @dataProvider providerCache
     */
    public function testTouchMultiTrue(CacheInterface $cache)
    {
        $this->markTestSkipped('Skipping: ' . __METHOD__);
    }

    /**
     * @dataProvider providerCache
     */
    public function testTouchMultiFalse(CacheInterface $cache)
    {
        $this->markTestSkipped('Skipping: ' . __METHOD__);
    }

    /**
     * @dataProvider providerCache
     */
    public function testIncrementWithTtl(CacheInterface $cache)
    {
        $this->markTestSkipped('Skipping: ' . __METHOD__);
    }

    /**
     * @dataProvider providerCache
     */
    public function testExistsByTouchFalse(CacheInterface $cache)
    {
        $this->markTestSkipped('Skipping: ' . __METHOD__);
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
 