<?php

namespace rockunit\core\session;

use rock\cache\Memcached;
use rock\session\MemorySession;

/**
 * @group base
 * @group cache
 * @group memcached
 */
class MemorySessionTest extends DbSessionTest
{
    /** @var  MemorySession */
    protected $handlerSession;
    protected function setUp()
    {
        if (!class_exists('\Memcached')) {
            $this->markTestSkipped(
                'The \Memcached is not available.'
            );
        }

        parent::setUp();
        $this->handlerSession = new MemorySession(['cache' => new Memcached()]);
        $this->handlerSession->removeAll();
    }

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        static::flush();
    }


    public function tearDown()
    {
        parent::tearDown();
        $this->handlerSession->removeAll();
    }

    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();
        static::flush();
    }

    public static function flush()
    {
        (new Memcached())->flush();
    }
}
 