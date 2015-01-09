<?php

namespace rockunit\extensions\mongodb;


use rockunit\core\session\CommonSessionTrait;
use rockunit\extensions\mongodb\mocks\MongoSessionMock;

class SessionAdvancedTest extends MongoDbTestCase
{
    use CommonSessionTrait;

    /**
     * @var string test session collection name.
     */
    protected static $sessionCollection = '_test_session';

    protected function setUp()
    {
        if (!class_exists('\MongoClient')) {
            $this->markTestSkipped(
                'The \MongoClient is not available.'
            );
        }

        parent::setUp();
        $config = [
            'connection' => $this->getConnection(),
            'sessionCollection' => static::$sessionCollection,
        ];
        $this->handlerSession = new MongoSessionMock($config);
        $this->handlerSession->init();
        $this->handlerSession->removeAll();
    }

    protected function tearDown()
    {
        $this->dropCollection(static::$sessionCollection);
        parent::tearDown();
    }

    public function testExpire()
    {
        $this->handlerSession->setTimeout(2);
        $this->handlerSession->add('ttl', 'test');
        $this->assertTrue($this->handlerSession->exists('ttl'));
        sleep(4);
        $this->assertNull($this->handlerSession->get('ttl'));
    }
}
