<?php

namespace rockunit\core\filters\verbs\EventFilterTest;


use rock\access\Access;
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

/**
 * @group base
 * @group filters
 */
class EventFilterTest extends \PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        Event::offAll();
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

    public function testTrigger()
    {
        $controller = new BarController();
        $this->assertSame($controller->method('actionIndex'), 'index');
        Event::trigger(BarController::className(), 'event_1');
        $this->expectOutputString('event_1');
    }
}
 