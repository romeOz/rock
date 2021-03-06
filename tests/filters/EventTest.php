<?php

namespace rockunit\filters;


use rock\core\ActionEvent;
use rock\core\Controller;
use rock\events\Event;
use rock\filters\AccessFilter;

/**
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
        $controller = new EventFilterController();
        Event::on($controller, 'test',function(){
            echo 'bar';
        });
        $controller->trigger('test');
        $this->assertSame('index', $controller->method('actionIndex'));
        $this->expectOutputString('bar');
    }

    public function testOn()
    {
        $controller = new EventFilterController();
        $controller->on('test', function(Event $event){
            echo 'test ' . $event->result;
        });
        $this->assertSame('view', $controller->actionView());
        $this->expectOutputString('test view');
    }

    public function testInlineOn()
    {
        $controller = new EventFilterController();
        $controller->{'on test'} = function(Event $event){
            echo 'test ' . $event->result;
        };
        $this->assertSame('view', $controller->actionView());
        $this->expectOutputString('test view');
    }

    public function testOnFail()
    {
        $controller = new EventFilterController();
        $controller->on('test', function(Event $event){
            echo 'test ' . $event->result;
        });
        $controller->{'as access'} = [
            'class' => AccessFilter::className(),
            'rules' => [
                'allow' => true,
                'verbs' => ['127.0.0.5'],
            ],
            'success' => function (AccessFilter $access) {
                $this->assertTrue($access->owner instanceof EventFilterController);
                echo 'success';
            },
            'fail' => function (AccessFilter $access) {
                $this->assertTrue($access->owner instanceof EventFilterController);
                echo 'fail';
            }
        ];
        $controller->detachBehavior('unknown');
        $this->assertNull($controller->actionView());
        $this->expectOutputString('fail');
    }
}

class EventFilterController extends Controller
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