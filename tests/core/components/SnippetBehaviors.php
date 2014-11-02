<?php

namespace rockunit\core\components\snippet\behavior;

use rock\access\Access;
use rock\base\Behavior;
use rock\base\ComponentsInterface;
use rock\base\ComponentsTrait;
use rock\base\Snippet;
use rock\di\Container;
use rock\event\Event;
use rock\exception\Exception;
use rock\filters\AccessFilter;
use rock\filters\EventFilter;
use rock\Rock;

class TestSnippet extends Snippet
{
    public function get()
    {
        return -5;
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


class SnippetAccessFalse extends Snippet
{
    public function behaviors()
    {
        return [
            'access_1' => [
                'class' => AccessFilter::className(),
                'rules' =>
                    [
                        'allow' => true,
                        //'ips'   => ['127.0.0.1'],
                        'verbs' => ['GET'],
                    ],
                'success' => [
                    function (Access $access) {
                        echo $access->owner instanceof self, $access->data['var'];
                    }, ['var' => 'success_1']
                ],
                'fail' => [
                    function (Access $access) {
                        echo $access->owner instanceof self, $access->data['var'];
                    }, ['var' => 'fail_1']
                ],
            ],
            'access_2' => [
                'class' => AccessFilter::className(),
                'rules' =>
                    [
                        'allow' => true,
                        'verbs' => ['POST'],
                    ],
                'success' => [
                    function (Access $access) {
                        echo $access->owner instanceof self, $access->data['var'];
                    }, ['var' => 'success_2']
                ],
                'fail' => [
                    function (Access $access) {
                        echo $access->owner instanceof self, $access->data['var'];
                    }, ['var' => 'fail_2']
                ],
            ],
            Foo::EVENT_BEGIN_GET => [
                'class' => EventFilter::className(),
                'on' => [
                    Foo::EVENT_BEGIN_GET => [
                        [
                            function () {
                                echo 'event';
                            }
                        ]
                    ],
                ],
                'trigger' => [Foo::EVENT_BEGIN_GET]
            ],
        ];
    }

    public function get()
    {
        return self::className();
    }
}


class SnippetAccessTrue extends Snippet
{
    public function behaviors()
    {
        return [
            'access_1' => [
                'class' => AccessFilter::className(),
                'rules' =>
                    [
                        'allow'     => true,
                        //'ips'   => ['127.0.0.1'],
                        'verbs'   => ['GET'],

                    ],


                'success' => [function(Access $access){
                    echo $access->owner instanceof self, $access->data['var'];
                }, ['var' => 'success_1']],
                'fail' => [function(Access $access){
                    echo $access->owner instanceof self, $access->data['var'];
                }, ['var' => 'fail']],
            ],
            'access_2' => [
                'class' => AccessFilter::className(),
                'rules' =>
                    [
                        'allow'     => false,
                        'verbs'   => ['POST'],
                    ],


                'success' => [function(Access $access){
                    echo $access->owner instanceof self, $access->data['var'];
                }, ['var' => 'success_2']],
                'fail' => [function(Access $access){
                    echo $access->owner instanceof self, $access->data['var'];
                }, ['var' => 'fail']],
            ],

            Foo::EVENT_BEGIN_GET => [
                'class'   => EventFilter::className(),
                'on'      => [
                    Foo::EVENT_BEGIN_GET => [
                        [function(){
                            echo 'event';
                        }]
                    ],
                ],
                'trigger' => [Foo::EVENT_BEGIN_GET]
            ],

        ];
    }

    public function get()
    {
        return self::className();
    }
}



class SnippetBehavior extends Snippet
{
    public function behaviors()
    {
        return [
            'event' => [
                'class'   => EventFilter::className(),
                'on'      => [
                    'event_5' => [
                        [function(){
                            echo 'event_5';
                        }]
                    ],
                ],
                'trigger' => ['event_5']
            ],
            'test' => [
                'class' => FooBehavior::className()
            ]
        ];
    }

    public function get()
    {
        return $this->bar();
    }
}


class SnippetBehaviorEvent extends Snippet
{
    public function get()
    {
        return 'test';
    }
}



class FooBehavior extends Behavior
{

    public $test = 'test';

    public function bar()
    {
        return 'bar';
    }
}

/**
 * @group base
 */
class SnippetBehaviorsTest extends \PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        static::tearDownAfterClass();
        Rock::$app->di[TestSnippet::className()] = [
            'class' => TestSnippet::className(),
        ];
        Rock::$app->di[SnippetAccessTrue::className()] = [
            'class' => SnippetAccessTrue::className(),
        ];
        Rock::$app->di[SnippetAccessFalse::className()] = [
            'class' => SnippetAccessFalse::className(),
        ];
    }

    public static function tearDownAfterClass()
    {

        Container::removeMulti([
                                        TestSnippet::className(),
                                        SnippetAccessTrue::className(),
                                        SnippetAccessFalse::className(),
                                    ]);

        Event::offAll();
    }
    
    
    public function testAccessTrue()
    {
        $this->assertEquals(Rock::$app->template
                                ->getSnippet(SnippetAccessTrue::className()),
                            SnippetAccessTrue::className()
        );

        $this->expectOutputString('1success_11success_2event');
    }

    public function testAccessFalse()
    {

        $this->assertNull(Rock::$app->template
                                ->getSnippet(SnippetAccessFalse::className())
        );
        $this->expectOutputString('1success_11fail_2');
    }

    public function testEventWithoutTrigger()
    {
       Rock::$app->di[SnippetBehaviorEvent::className()] = [
            'class' => SnippetBehaviorEvent::className(),
        ];
        /** @var ComponentsInterface $snippetBehaviorEvent */
        $snippetBehaviorEvent = Rock::$app->{SnippetBehaviorEvent::className()};

        $snippetBehaviorEvent->{'on event_5'} = function(){
            echo 'event_5';
        };
        $snippetBehaviorEvent->{'trigger event_5'} = true;
        Rock::$app->template->getSnippet($snippetBehaviorEvent);
        //Event::trigger(SnippetBehaviorEvent::className(), 'event_5');
        $this->expectOutputString('event_5');
        Rock::$app->di->remove(SnippetBehaviorEvent::className());
    }


    public function testEventTrigger()
    {
        Rock::$app->di[SnippetBehaviorEvent::className()] = [
            'class' => SnippetBehaviorEvent::className(),
        ];

        /** @var ComponentsInterface $snippetBehaviorEvent */
        $snippetBehaviorEvent = Rock::$app->{SnippetBehaviorEvent::className()};

        $snippetBehaviorEvent->attachBehaviors(
            [
                'event' => [
                    'class'   => EventFilter::className(),
                    'on'      => [
                        'event_5' => [
                            [function(){
                                echo 'event_5';
                            }]
                        ],
                    ],
                    'trigger' => ['event_5']
                ],
            ]
        );
        $this->assertEquals(Rock::$app->template->getSnippet($snippetBehaviorEvent), 'test');
        $this->expectOutputString('event_5');
        Rock::$app->di->remove(SnippetBehaviorEvent::className());
    }


    public function testAttachBehavior()
    {
        Rock::$app->di[Foo::className()] = [
            'class' => Foo::className(),
        ];
        Rock::$app->di[SnippetBehavior::className()] = [
            'class' => SnippetBehavior::className(),
        ];


        $this->assertEquals(Rock::$app->template->getSnippet(SnippetBehavior::className()), 'bar');
        $this->expectOutputString('event_5');
        Rock::$app->di->remove(SnippetBehavior::className());
    }


    /**
     * @expectedException Exception
     */
    public function testAttachBehaviorSnippetAsInstanceThrowException()
    {
        Rock::$app->di[Foo::className()] = [
            'class' => Foo::className(),
        ];

        Rock::$app->di[SnippetBehavior::className()] = [
            'class' => SnippetBehavior::className(),
        ];
        $snippetBehavior = Rock::$app->{SnippetBehavior::className()};
        $snippetBehavior->detachBehaviors();
        Rock::$app->template->getSnippet($snippetBehavior);
        $this->expectOutputString('event_5');
        Rock::$app->di->remove(SnippetBehavior::className());
    }




    public function testAttachBehaviorFail()
    {
        Rock::$app->di[Foo::className()] = [
            'class' => Foo::className(),
        ];

        Rock::$app->di[SnippetBehavior::className()] = [
            'class' => SnippetBehavior::className(),
        ];
        $snippetBehavior = Rock::$app->{SnippetBehavior::className()};

        $this->assertTrue($snippetBehavior->hasBehavior('test'));
        $this->assertTrue($snippetBehavior->hasBehavior('event'));
        $snippetBehavior->detachBehaviors();
        $this->assertFalse($snippetBehavior->hasBehavior('test'));
        $this->assertFalse($snippetBehavior->hasBehavior('event'));

        Rock::$app->di->remove(SnippetBehavior::className());
    }
}