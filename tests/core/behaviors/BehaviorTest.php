<?php
namespace rockunit\core\behavior\Behavior;



use rock\base\Behavior;
use rock\base\ComponentsInterface;
use rock\base\ComponentsTrait;
use rock\base\Controller;
use rock\event\Event;
use rock\exception\Exception;
use rock\filters\EventFilter;
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

class FooBehavior extends Behavior
{

    public $test = 'test';

    public function bar()
    {
        return 'bar';
    }
}



class FooController extends Controller
{

}

/**
 * @group base
 */
class BehaviorTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        static::tearDownAfterClass();
    }

    public static function tearDownAfterClass()
    {
        Event::offAll();
    }


    public function testAttachBehavior()
    {
        Rock::$app->di[Foo::className()] = [
            'class' => Foo::className(),
            'singleton' => true,
        ];
        Rock::$app->di[FooController::className()] = [
            'class' => FooController::className(),
            'singleton' => true,
        ];
        /** @var ComponentsInterface $controller */
        $controller = Rock::$app->{FooController::className()};
        $controller->attachBehaviors(
            [
                'test' => [
                    'class' => FooBehavior::className()
                ]
            ]
        );
        $controller->{'as Event'} = [
            'class' => EventFilter::className(),
            'on' => [
                'event_5' => [
                    [
                        function () {
                            echo 'event_5';
                        }
                    ]
                ],
            ],
            'trigger' => ['event_5']
        ];
        $this->assertEquals($controller->bar(), 'bar');
        $controller->before();
        $this->expectOutputString('event_5');
        $this->assertTrue(isset($controller->test));
        $this->assertEquals($controller->test, 'test');
        unset($controller->test);
        $this->assertFalse(isset($controller->test));
        $this->assertNull($controller->test);
        $controller->test = 'foo';
        $this->assertEquals($controller->test, 'foo');
    }


    /**
     * @expectedException Exception
     */
    public function testAttachBehaviorDetachThrowException()
    {
        Rock::$app->di[Foo::className()] = [
            'class' => Foo::className(),
            'singleton' => true,
        ];
        Rock::$app->di[FooController::className()] = [
            'class' => FooController::className(),
            'singleton' => true,
        ];
        /** @var ComponentsInterface $controllerBehavior */
        $controllerBehavior = Rock::$app->{FooController::className()};
        $controllerBehavior->attachBehaviors(
            [
                'test' => [
                    'class' => Foo::className()
                ]
            ]
        );
        $this->assertEquals($controllerBehavior->bar(), 'bar');
        $controllerBehavior->detachBehavior('test');
        $this->assertEquals($controllerBehavior->bar(), 'bar');
    }

    public function testAttachBehaviorDetach()
    {
        Rock::$app->di[Foo::className()] = [
            'class' => Foo::className(),
            'singleton' => true,
        ];
        Rock::$app->di[FooController::className()] = [
            'class' => FooController::className(),
            'singleton' => true,
        ];
        /** @var ComponentsInterface $controllerBehavior */
        $controllerBehavior = Rock::$app->{FooController::className()};
        $controllerBehavior->attachBehaviors(
            [
                'test' => [
                    'class' => FooBehavior::className()
                ]
            ]
        );
        $this->assertTrue($controllerBehavior->hasBehavior('test'));
        $this->assertEquals($controllerBehavior->bar(), 'bar');
        $controllerBehavior->detachBehavior('test');
        $this->assertFalse($controllerBehavior->hasBehavior('test'));
    }


    public function testAttachBehaviorAttach()
    {
        Rock::$app->di[Foo::className()] = [
            'class' => Foo::className(),
            'singleton' => true,
        ];
        Rock::$app->di[FooController::className()] = [
            'class' => FooController::className(),
            'singleton' => true,
        ];
        /** @var ComponentsInterface $controllerBehavior */
        $controllerBehavior = Rock::$app->{FooController::className()};
        $controllerBehavior->attachBehaviors(
            [
                'test' => [
                    'class' => FooBehavior::className()
                ]
            ]
        );
        $this->assertEquals($controllerBehavior->bar(), 'bar');
        $controllerBehavior->detachBehaviors();
        $this->assertFalse($controllerBehavior->hasBehavior('test'));
        $controllerBehavior->attachBehavior(
            'test',
            [
                'class' => FooBehavior::className()
            ]
        );
        $this->assertTrue($controllerBehavior->hasBehavior('test'));
        $this->assertEquals($controllerBehavior->bar(), 'bar');
    }
}