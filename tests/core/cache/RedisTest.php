<?php

namespace rockunit\core\cache;

use rock\cache\CacheInterface;
use rock\cache\Redis;

/**
 * @group cache
 * @group redis
 */
class RedisTest extends \PHPUnit_Framework_TestCase
{
    use CommonCacheTrait;

    public static function flush()
    {
        (new Redis())->flush();
    }

    public function init($serialize, $lock = true)
    {
        if (!class_exists('\Redis')) {
            $this->markTestSkipped(
                'The \Redis is not available.'
            );
        }
        return new Redis(['serializer' => $serialize, 'lock' => $lock]);
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
     * @expectedException \rock\cache\CacheException
     */
    public function testGetAll(CacheInterface $cache)
    {
        $cache->getAll();
    }
}
 