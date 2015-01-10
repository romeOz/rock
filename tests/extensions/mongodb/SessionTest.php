<?php

namespace rockunit\extensions\mongodb;

use rock\di\Container;
use rock\mongodb\Session;

/**
 * @group mongodb
 */
class SessionTest extends MongoDbTestCase
{
    /**
     * @var string test session collection name.
     */
    protected static $sessionCollection = '_test_session';

    protected function tearDown()
    {
        $this->dropCollection(static::$sessionCollection);
        parent::tearDown();
    }

    /**
     * Creates test session instance.
     * @return Session session instance.
     */
    protected function createSession()
    {
        return Container::load([
            'class' => Session::className(),
            'connection' => $this->getConnection(),
            'sessionCollection' => static::$sessionCollection,
        ]);
    }

    // Tests:

    public function testWriteSession()
    {
        $session = $this->createSession();

        $id = uniqid();
        $data = [
            'name' => 'value'
        ];
        $dataSerialized = serialize($data);
        $this->assertTrue($session->writeSession($id, $dataSerialized), 'Unable to write session!');

        $collection = $session->connection->getCollection($session->sessionCollection);
        $rows = $this->findAll($collection);
        $this->assertCount(1, $rows, 'No session record!');

        $row = array_shift($rows);
        $this->assertEquals($id, $row['id'], 'Wrong session id!');
        $this->assertEquals($dataSerialized, $row['data'], 'Wrong session data!');
        $this->assertTrue($row['expire'] > new \MongoDate(time()), 'Wrong session expire!');

        $newData = [
            'name' => 'new value'
        ];
        $newDataSerialized = serialize($newData);
        $this->assertTrue($session->writeSession($id, $newDataSerialized), 'Unable to update session!');

        $rows = $this->findAll($collection);
        $this->assertCount(1, $rows, 'Wrong session records after update!');
        $newRow = array_shift($rows);
        $this->assertEquals($id, $newRow['id'], 'Wrong session id after update!');
        $this->assertEquals($newDataSerialized, $newRow['data'], 'Wrong session data after update!');
        $this->assertTrue($newRow['expire'] >= $row['expire'], 'Wrong session expire after update!');
    }

    /**
     * @depends testWriteSession
     */
    public function testDestroySession()
    {
        $session = $this->createSession();

        $id = uniqid();
        $data = [
            'name' => 'value'
        ];
        $dataSerialized = serialize($data);
        $session->writeSession($id, $dataSerialized);

        $this->assertTrue($session->destroySession($id), 'Unable to destroy session!');

        $collection = $session->connection->getCollection($session->sessionCollection);
        $rows = $this->findAll($collection);
        $this->assertEmpty($rows, 'Session record not deleted!');
    }

    /**
     * @depends testWriteSession
     */
    public function testReadSession()
    {
        $session = $this->createSession();

        $id = uniqid();
        $data = [
            'name' => 'value'
        ];
        $dataSerialized = serialize($data);
        $session->writeSession($id, $dataSerialized);

        $sessionData = $session->readSession($id);
        $this->assertEquals($dataSerialized, $sessionData, 'Unable to read session!');

        $collection = $session->connection->getCollection($session->sessionCollection);
        list($row) = $this->findAll($collection);
        $newRow = $row;
        $newRow['expire'] = new \MongoDate(time() - 1);
        unset($newRow['_id']);
        $collection->update(['_id' => $row['_id']], $newRow);

        $sessionData = $session->readSession($id);
        $this->assertEquals('', $sessionData, 'Expired session read!');
    }
}