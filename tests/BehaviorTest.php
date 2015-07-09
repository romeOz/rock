<?php
namespace rockunit;

use rock\components\Behavior;
use rock\events\Event;
use rock\filters\AccessFilter;
use rock\core\ActionEvent;
use rock\core\Controller;

/**
 * @group behaviors
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
        $controller = new FooController();
        $controller->attachBehaviors(
            [
                'testBehavior' => [
                    'class' => FooBehavior::className()
                ]
            ]
        );

        $this->assertTrue($controller->existsBehavior('testBehavior'));
        $this->assertSame('fooBehavior', $controller->test);
        $this->assertSame('bar', $controller->bar());
        unset($controller->test);
        $this->assertNull($controller->test);
        $controller->test = 'foo';
        $this->assertEquals('foo', $controller->test);
    }

    /**
     * @expectedException \rock\base\BaseException
     */
    public function testAttachBehaviorDetachThrowException()
    {
        $controller = new FooController();
        $controller->attachBehaviors(
            [
                'testBehavior' => [
                    'class' => FooBehavior::className()
                ]
            ]
        );

        $this->assertTrue($controller->existsBehavior('testBehavior'));
        $controller->detachBehavior('testBehavior');
        $this->assertFalse($controller->existsBehavior('testBehavior'));
        $controller->bar();

    }

    public function testDetachBehaviors()
    {
        $controller = new FooController();
        $controller->on('test', function (Event $event) {
            echo 'test ' . $event->result;
        });
        $controller->detachBehaviors();
        $this->assertSame('view', $controller->actionView());
        $this->expectOutputString('test view');
    }

    public function testDetachBehavior()
    {
        $controller = new FooController();
        $controller->on('test', function (Event $event) {
            echo 'test ' . $event->result;
        });
        $controller->{'as access'} = [
            'class' => AccessFilter::className(),
            'rules' => [
                'allow' => true,
                'ips' => ['127.0.0.5'],
            ],
            'success' => function (AccessFilter $access) {
                $this->assertTrue($access->owner instanceof FooController);
                echo 'success';
            },
            'fail' => function (AccessFilter $access) {
                $this->assertTrue($access->owner instanceof FooController);
                echo 'fail';
            }
        ];
        $this->assertNull($controller->actionView());
        $this->assertTrue($controller->existsBehavior('access'));
        $this->assertInstanceOf(Behavior::className(), $controller->detachBehavior('access'));
        $this->assertFalse($controller->existsBehavior('access'));
        $this->assertSame('view', $controller->actionView());
        $this->expectOutputString('failtest view');
    }
}


class FooBehavior extends Behavior
{

    public $test = 'fooBehavior';

    public function bar()
    {
        return 'bar';
    }
}


class FooController extends Controller
{
    public function actionIndex()
    {
        return 'index';
    }

    public function actionView()
    {
        if (!$this->beforeAction('actionView')) {
            return null;
        }
        $event = new ActionEvent('actionView');
        $event->result = 'view';
        $this->trigger('test', $event);
        return 'view';
    }
}