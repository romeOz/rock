<?php

namespace rockunit\core\filters\EventTest;


use rock\access\Access;
use rock\components\ActionEvent;
use rock\core\Controller;
use rock\events\Event;
use rock\filters\AccessFilter;

/**
 * @group base
 * @group filters
 */
class EventTest extends \PHPUnit_Framework_TestCase
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

    public function test_()
    {
        $controller = new FooController();
        Event::on($controller, 'test',function(){
            echo 'bar';
        });
        $controller->trigger('test');
        $this->assertSame('index', $controller->method('actionIndex'));
        $this->expectOutputString('bar');
    }

    public function testOn()
    {
        $controller = new FooController();
        $controller->on('test', function(Event $event){
            echo 'test ' . $event->result;
        });
        $this->assertSame('view', $controller->actionView());
        $this->expectOutputString('test view');
    }

    public function testInlineOn()
    {
        $controller = new FooController();
        $controller->{'on test'} = function(Event $event){
            echo 'test ' . $event->result;
        };
        $this->assertSame('view', $controller->actionView());
        $this->expectOutputString('test view');
    }

    public function testOnFail()
    {
        $controller = new FooController();
        $controller->on('test', function(Event $event){
            echo 'test ' . $event->result;
        });
        $controller->{'as access'} = [
            'class' => AccessFilter::className(),
            'rules' => [
                'allow' => true,
                'verbs' => ['POST'],
            ],
            'success' => function (Access $access) {
                $this->assertTrue($access->owner instanceof FooController);
                echo 'success';
            },
            'fail' => function (Access $access) {
                $this->assertTrue($access->owner instanceof FooController);
                echo 'fail';
            }
        ];
        $controller->detachBehavior('unknown');
        $this->assertNull($controller->actionView());
        $this->expectOutputString('fail');
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