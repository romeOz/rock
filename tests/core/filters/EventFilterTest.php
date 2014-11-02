<?php

namespace rockunit\core\filters\verbs\EventFilterTest;


use rock\access\Access;
use rock\base\ClassName;
use rock\base\ComponentsTrait;
use rock\base\Controller;
use rock\event\Event;
use rock\filters\AccessFilter;
use rock\filters\EventFilter;

class FooController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessFilter::className(),
                'only' => ['actionIndex'],
                'rules' =>
                    [
                        'allow' => true,
                        'verbs' => ['PUT'],
                        'roles' => ['editor']
                    ],
                'fail' =>  function(Access $access){
                    echo $access->isErrorRoles().$access->isErrorIps().$access->isErrorCustom().$access->isErrorVerbs();
                },
            ],
            'event_1' => [
                'class' => EventFilter::className(),
                'only' => ['actionIndex'],
                'on'      => [
                    'event_1' => [
                        function(){
                            echo 'event_1';
                        }
                    ],
                ],
                'trigger' => ['event_1']
            ],

            'event_2' => [
                'class' => EventFilter::className(),
                'only' => ['actionView'],
                'on'      => [
                    'event_2' => [
                        function(){
                            echo 'event_2';
                        }
                    ],
                ],
                'trigger' => ['event_2']
            ],
        ];
    }


    public function actionIndex()
    {
        return 'index';
    }

    public function actionView()
    {
        if ($this->before(__METHOD__) === false) {
            return null;
        }
        $result = 'view';
        if ($this->after('actionView', $result) === false) {
            return null;
        }

        return $result;
    }
}



class BarController extends Controller
{
    public function behaviors()
    {
        return [

            'event_1' => [
                'class' => EventFilter::className(),
                'only' => ['actionIndex'],
                'on'      => [
                    'event_1' => [
                        function(){
                            echo 'event_1';
                        }
                    ],
                ],
            ],
        ];
    }


    public function actionIndex()
    {
        return 'index';
    }
}


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
 * @group filters
 */
class EventFilterTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        static::tearDownAfterClass();
    }

    public static function setUpBeforeClass()
    {
        static::tearDownAfterClass();
    }

    public static function tearDownAfterClass()
    {
        Event::offAll();
    }
    public function testFail()
    {
        $controller = new FooController();
        $this->assertNull($controller->method('actionIndex'));
        $this->expectOutputString('11');
    }

    public function testSuccess()
    {
        $controller = new FooController();
        $this->assertSame($controller->actionView(), 'view');
        $this->expectOutputString('event_2');
    }

    public function testTriggerA()
    {
        $controller = new BarController();
        $this->assertSame($controller->method('actionIndex'), 'index');
        Event::trigger(BarController::className(), 'event_1');
        $this->expectOutputString('event_1');
    }

    public function testTriggerB()
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

    public function testEventByMethodWithoutEvents()
    {
        $this->assertEquals('bar', (new Foo())->method('bar'));
        $this->assertEquals(0, Event::count());
        $this->expectOutputString('');
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

}
 