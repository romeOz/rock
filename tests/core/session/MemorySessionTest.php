<?php

namespace rockunit\core\session;

use rock\cache\Memcached;
use rockunit\core\session\mocks\MemorySessionMock;

/**
 * @group base
 * @group cache
 * @group memcached
 */
class MemorySessionTest extends \PHPUnit_Framework_TestCase
{
    use CommonSessionTrait;

    protected function setUp()
    {
        if (!class_exists('\Memcached')) {
            $this->markTestSkipped(
                'The \Memcached is not available.'
            );
        }

        parent::setUp();
        $this->handlerSession = new MemorySessionMock(['cache' => static::getStorage()]);
        $this->handlerSession->removeAll();
    }

    protected static function getStorage()
    {
        return new Memcached();
    }

//    public static function setUpBeforeClass()
//    {
//        parent::setUpBeforeClass();
//        static::flush();
//    }


    public function tearDown()
    {
        parent::tearDown();
        if (isset($this->handlerSession)) {
            $this->handlerSession->destroy();
        }
    }

    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();
        static::flush();
    }

    public static function flush()
    {
        static::getStorage()->flush();
    }

    public function testExpire()
    {
        $this->handlerSession->setTimeout(2);
        $this->handlerSession->add('ttl', 'test');
        $this->assertTrue($this->handlerSession->has('ttl'));
        sleep(4);
        $this->assertNull($this->handlerSession->get('ttl'));
    }
}
 