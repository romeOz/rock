<?php

namespace rockunit\core\components;

use rock\access\Access;
use rock\base\ClassName;
use rock\base\ComponentsTrait;
use rock\event\Event;
use rock\helpers\Sanitize;
use rock\Rock;


class Foo
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

class TestEvent
{
    use ClassName;

    public static function beginGet(Event $event)
    {
        echo $event->owner instanceof Foo;
    }

    public static function endGet(Event $event)
    {
        echo $event->owner instanceof Foo,  $event->data['result'];
    }


    public static function foo(Event $event)
    {
        echo $event->data['foo'];
    }
}

/**
 * @group base
 */
class ComponentsTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        static::tearDownAfterClass();
    }

    public static function tearDownAfterClass()
    {
        Event::offAll();
    }


    public function testEventByMethodWithoutEvents()
    {
        $this->assertEquals(
            (new Foo())
                ->method('bar'),
            'bar'
        );
        $this->assertEquals(Event::count(), 0);
        $this->expectOutputString('');
    }


    public function testTrigger()
    {
        $this->assertEquals(
            (new Foo())
                ->on('foo', [[TestEvent::className(), 'foo'], ['foo' => 'foo']])
                ->trigger('foo')
                ->foo(),
            'foo'
        );
        $this->assertFalse(Event::has(Foo::className(), 'foo'));
        $this->expectOutputString('foo');
    }

    public function testOff()
    {
        Event::on(Foo::className(), 'foo', [[TestEvent::className(), 'foo'], ['foo' => 'foo']]);
        $this->assertEquals(
            (new Foo())
                ->off('foo')
                ->trigger('foo')
                ->foo(),
            'foo'
        );
        $this->assertFalse(Event::has(Foo::className(),'foo'));
        $this->expectOutputString('');
    }

    public function testCheckAccessTrue()
    {
        $this->assertEquals(
            (new Foo())
                ->checkAccess(
                    [
                        'allow' => true,
                        'verbs' => ['GET'],
                    ],
                    [
                        function (Access $access) {
                            $this->assertTrue($access->owner instanceof Foo);
                            echo 'success';
                        }
                    ],
                    [
                        function (Access $access) {
                            $this->assertTrue($access->owner instanceof Foo);
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
            (new Foo())

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
                            $this->assertTrue($access->owner instanceof Foo);
                            echo 'success';
                        }
                    ],
                    [
                        function (Access $access) {
                            $this->assertTrue($access->owner instanceof Foo);
                            echo 'fail';
                        }
                    ]
                )
                ->trigger('event_after', Event::AFTER)
                ->foo()
        );
        $this->assertFalse(Event::has(Foo::className(),'event_after'));
        $this->expectOutputString('event_beforefail');
    }


    public function testCheckAccessFalseByMethod()
    {
        $this->assertNull(
            (new Foo())
                ->checkAccess(
                    [
                        'allow' => true,
                        'verbs' => ['POST'],
                    ],
                    [
                        function (Access $access) {
                            $this->assertTrue($access->owner instanceof Foo);
                            echo 'success';
                        }
                    ],
                    [
                        function (Access $access) {
                            $this->assertTrue($access->owner instanceof Foo);
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
            (new Foo())
                ->checkAccess(
                    [
                        'allow' => true,
                        'ips' => ['127.0.0.1'],
                    ],
                    [
                        function (Access $access) {
                            $this->assertTrue($access->owner instanceof Foo);
                            echo 'success';
                        }
                    ],
                    [
                        function (Access $access) {
                            $this->assertTrue($access->owner instanceof Foo);
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
            (new Foo())
                ->checkAccess(
                    [
                        'allow' => true,
                        'ips' => ['127.0.0.2'],
                    ],
                    [
                        function (Access $access) {
                            $this->assertTrue($access->owner instanceof Foo);
                            echo 'success';
                        }
                    ],
                    [
                        function (Access $access)  {
                            $this->assertTrue($access->owner instanceof Foo);
                            echo 'fail';
                        }
                    ]
                )
                ->foo()
        );
        $this->expectOutputString('fail');
    }


    public function testFilters()
    {
        $this->assertEquals(
            (new Foo())
                ->filters([Sanitize::STRIP_TAGS])
                ->method('filter'),
            'test'
        );
    }

    public function testValidationTrue()
    {
        $this->assertEquals(
            (new Foo())
                ->filters([Sanitize::STRIP_TAGS])
                ->validation(Rock::$app->validation->string())
                ->method('filter'),
            'test'
        );
    }


    public function testValidationFalse()
    {
        $this->assertNull(
            (new Foo())
                ->filters([Sanitize::STRIP_TAGS])
                ->validation(
                    function ($result) {
                        return Rock::$app->validation->numeric()->validate($result);
                    }
                )
                ->method('filter')
        );
    }
}
 