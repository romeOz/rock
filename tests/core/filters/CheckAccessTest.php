<?php

namespace rockunit\core\filters;

use rock\access\Access;
use rock\base\ComponentsTrait;
use rock\event\Event;

class BazController
{
    use ComponentsTrait;
    const EVENT_BEGIN_GET = 'beginGet';
    const EVENT_END_GET = 'endGet';

    public function foo()
    {
        if ($this->before('foo') === false) {
            return null;
        }
        $result = 'foo';
        if ($this->after(null, $result) === false) {
            return null;
        };
        return $result;
    }

    public function bar()
    {
        return 'bar';
    }


    public function filter()
    {
        return '<b>test</b>';
    }

    public $test = 'test';
    //    public function beforeAction()
    //    {
    //
    //        return parent::beforeAction();
    //    }
}

/**
 * @group base
 * @group filters
 */
class CheckAccessTest  extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        static::tearDownAfterClass();
    }

    public static function tearDownAfterClass()
    {
        Event::offAll();
    }

    public function testCheckAccessTrue()
    {
        $this->assertEquals(
            (new BazController())
                ->checkAccess(
                    [
                        'allow' => true,
                        'verbs' => ['GET'],
                    ],
                    [
                        function (Access $access) {
                            $this->assertTrue($access->owner instanceof BazController);
                            echo 'success';
                        }
                    ],
                    [
                        function (Access $access) {
                            $this->assertTrue($access->owner instanceof BazController);
                            echo 'fail';
                        }
                    ]
                )
                ->on(
                    'event_before',
                    [
                        function () {
                            echo 'event_before';
                        }
                    ]
                )
                ->on(
                    'event_after',
                    [
                        function (Event $event) {

                            echo 'event_after' . $event->result;
                        }
                    ],
                    Event::AFTER
                )
                ->trigger('event_before')
                ->trigger('event_after', Event::AFTER)
                ->foo(),
            'foo'
        );
        $this->expectOutputString('successevent_beforeevent_afterfoo');
    }


    public function testCheckAccessFalse()
    {
        $this->assertNull(
            (new BazController())

                ->on(
                    'event_before',
                    [
                        function () {
                            echo 'event_before';
                        }
                    ]
                )
                ->on(
                    'event_after',
                    [
                        function (Event $event) {
                            echo 'event_after' . $event->result;
                        }
                    ],
                    Event::AFTER
                )
                ->trigger('event_before')
                ->checkAccess(
                    [
                        'allow' => true,
                        'verbs' => ['POST'],
                    ],
                    [
                        function (Access $access) {
                            $this->assertTrue($access->owner instanceof BazController);
                            echo 'success';
                        }
                    ],
                    [
                        function (Access $access) {
                            $this->assertTrue($access->owner instanceof BazController);
                            echo 'fail';
                        }
                    ]
                )
                ->trigger('event_after', Event::AFTER)
                ->foo()
        );
        $this->assertFalse(Event::has(BazController::className(),'event_after'));
        $this->expectOutputString('event_beforefail');
    }


    public function testCheckAccessFalseByMethod()
    {
        $this->assertNull(
            (new BazController())
                ->checkAccess(
                    [
                        'allow' => true,
                        'verbs' => ['POST'],
                    ],
                    [
                        function (Access $access) {
                            $this->assertTrue($access->owner instanceof BazController);
                            echo 'success';
                        }
                    ],
                    [
                        function (Access $access) {
                            $this->assertTrue($access->owner instanceof BazController);
                            echo 'fail';
                        }
                    ]
                )
                ->method('bar')
        );
        $this->expectOutputString('fail');
    }


    public function testCheckAccessIpsTrue()
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $this->assertEquals(
            (new BazController())
                ->checkAccess(
                    [
                        'allow' => true,
                        'ips' => ['127.0.0.1'],
                    ],
                    [
                        function (Access $access) {
                            $this->assertTrue($access->owner instanceof BazController);
                            echo 'success';
                        }
                    ],
                    [
                        function (Access $access) {
                            $this->assertTrue($access->owner instanceof BazController);
                            echo 'fail';
                        }
                    ]
                )
                ->foo(),
            'foo'
        );
        $this->expectOutputString('success');
    }

    public function testCheckAccessIpsFalse()
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $this->assertNull(
            (new BazController())
                ->checkAccess(
                    [
                        'allow' => true,
                        'ips' => ['127.0.0.2'],
                    ],
                    [
                        function (Access $access) {
                            $this->assertTrue($access->owner instanceof BazController);
                            echo 'success';
                        }
                    ],
                    [
                        function (Access $access)  {
                            $this->assertTrue($access->owner instanceof BazController);
                            echo 'fail';
                        }
                    ]
                )
                ->foo()
        );
        $this->expectOutputString('fail');
    }
} 