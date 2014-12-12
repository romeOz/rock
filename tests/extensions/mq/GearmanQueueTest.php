<?php

namespace rockunit\extensions\mq;


use rock\mq\GearmanQueue;

/**
 * @group mq
 * @group gearman
 */
class GearmanQueueTest extends \PHPUnit_Framework_TestCase
{

    public function testSend()
    {
        //non blocking
        $gearman = new GearmanQueue();
        $gearman->blocking = false;
        $gearman->sendBackground('background');

        //blocking
        $gearman->blocking = true;
        $gearman->sendBackground('background');

        //non blocking
        $gearman->blocking = false;
        $this->assertSame('I am server: test', $gearman->send('test', 2));

        //blocking
        $gearman->blocking = true;
        $this->assertSame('I am server: test', $gearman->send('test', 2));

        // unknown queueName
        $gearman->id = 'demo';
        $gearman->blocking = false;
        $this->assertNull($gearman->send('test', 2));
    }
}
 