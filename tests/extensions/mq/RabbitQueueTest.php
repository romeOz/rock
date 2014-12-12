<?php

namespace rockunit\extensions\mq;


use PhpAmqpLib\Message\AMQPMessage;
use rock\mq\RabbitQueue;

/**
 * @group mq
 * @group rabbitmq
 */
class RabbitQueueTest extends \PHPUnit_Framework_TestCase
{
    public function testSendBackground()
    {
        $rabbit = new RabbitQueue();
        $rabbit->id = 'simple';
        $rabbit->blocking = false;
        $rabbit->sendBackground('testBackground');
    }

    public function testSend()
    {
        //non blocking
        $rabbit = new RabbitQueue();
        $rabbit->id = 'simple';
        $rabbit->blocking = false;
        $this->assertSame('Hi! I am server: test', $rabbit->send('test', 2));

        //blocking
        $rabbit->blocking = true;
        $this->assertSame('Hi! I am server: block', $rabbit->send('block', 2));

        //return message

        //non blocking
        $rabbit = new RabbitQueue();
        $rabbit->id = 'simple';
        $rabbit->blocking = false;
        $corr_id = uniqid();
        $message = null;
        $rabbit->properties['correlation_id'] = $corr_id;
        $rabbit->properties['delivery_mode'] = 2;
        $callback = function(AMQPMessage $msg) use (&$message, $corr_id) {
            if($msg->get('correlation_id') == $corr_id) {
                $message = $msg->body;
            }
        };
        $rabbit->send('test', -1, $callback);
        $this->assertSame('Hi! I am server: test', $message);

        //blocking
        $rabbit->blocking = true;
        $rabbit->send('block', -1, $callback);
        $this->assertSame('Hi! I am server: block', $message);

        // unknown queueName
        $rabbit->id = 'demo';
        $rabbit->timeout = 3;
        $rabbit->blocking = false;
        $this->assertNull($rabbit->send('test', 2));
    }

    public function testPubSub()
    {
        //non blocking
        $rabbit = new RabbitQueue();
        $rabbit->blocking = false;
        $rabbit->type = 'direct';
        $rabbit->exchange = 'direct_test';
        $this->assertSame('Hi! I am server: foo test', $rabbit->subscribe('foo', -1, 'test'));
        $this->assertSame('Hi! I am server: bar', $rabbit->subscribe('bar', 2,'test'));
        //unknown
        $this->assertNull($rabbit->subscribe('unknown', 2,'test'));
        //blocking
        $rabbit->blocking = true;
        $this->assertSame('Hi! I am server: foo test', $rabbit->subscribe('foo', -1, 'test'));
        $this->assertSame('Hi! I am server: bar', $rabbit->subscribe('bar', 2,'test'));
    }
}