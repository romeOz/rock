<?php
namespace rockunit\core\cache;


use rock\cache\APC;
use rock\cache\CacheInterface;
use rock\cache\Exception;

/**
 * @group cache
 * @group apc
 */
class APCTest extends \PHPUnit_Framework_TestCase
{
    use  CommonTraitTest;

    public static function flush()
    {
        (new APC(['enabled' => true]))->flush();
    }

    public function init($serialize)
    {
        $cache = new APC(['enabled' => true, 'serializer' => $serialize]);
        return $cache;
    }

    /**
     * @dataProvider providerCache
     * @expectedException Exception
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

    }

    /**
     * @dataProvider providerCache
     */
    public function testHasByTouchFalse(CacheInterface $cache)
    {

    }

    /**
     * @dataProvider providerCache
     */
    public function testTouch(CacheInterface $cache)
    {

    }

    /**
     * @dataProvider providerCache
     */
    public function testTouchMultiTrue(CacheInterface $cache)
    {

    }

    /**
     * @dataProvider providerCache
     */
    public function testTouchMultiFalse(CacheInterface $cache)
    {

    }

    /**
     * @dataProvider providerCache
     */
    public function testIncrementWithTtl(CacheInterface $cache)
    {

    }
}
 