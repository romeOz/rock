<?php

namespace rockunit\extensions\mq;


use rock\mq\ZeroQueue;

/**
 * @group mq
 * @group zeromq
 */
class ZeroQueueTest extends \PHPUnit_Framework_TestCase
{
    public function testSend()
    {
        //non blocking
        $zero = new ZeroQueue();
        $zero->blocking = false;
        $zero->dns = 'tcp://127.0.0.1:5557';
        $zero->sendBackground('background');

        //blocking
        $zero->blocking = true;
        $zero->sendBackground('background');

        //return message

        //non blocking
        $zero->blocking = false;
        $this->assertSame('I am server: test', $zero->send('test', 2));

        //blocking
        $zero->blocking = true;
        $this->assertSame('I am server: test', $zero->send('test', 2));
    }

    public function testPubSub()
    {
        //non blocking
        $zero = new ZeroQueue();
        $zero->dns = 'tcp://127.0.0.1:5558';
        $zero->blocking = false;
        $this->assertSame('I am server: foo', $zero->subscribe('foo'));
        $this->assertSame('I am server: foo', $zero->subscribe('foo'));
        $this->assertSame('I am server: bar', $zero->subscribe('bar'));
        //unknown
        $this->assertNull($zero->subscribe('unknown', 2));

        $zero->blocking = true;
        $this->assertSame('I am server: foo', $zero->subscribe('foo'));
        $this->assertSame('I am server: foo', $zero->subscribe('foo'));
        $this->assertSame('I am server: bar', $zero->subscribe('bar'));
    }
}
 