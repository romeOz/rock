<?php

namespace rockunit\core\components\snippet;

use rock\access\Access;
use rock\base\ComponentsTrait;
use rock\base\Snippet;
use rock\di\Container;
use rock\event\Event;
use rock\filters\AccessFilter;
use rock\filters\EventFilter;
use rock\Rock;
use rock\template\Template;

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

class TestSnippet extends Snippet
{
    public function get()
    {
        return -5;
    }
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

/**
 * @group base
 */
class SnippetTest extends \PHPUnit_Framework_TestCase {

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
                                   SnippetAccessFalse::className()
                               ]);
        Event::offAll();
    }

    public function testCheckAccessTrue()
    {
        $this->assertEquals(Rock::$app->template
                              ->checkAccess(
                              [
                                  'allow'     => true,
                                  'verbs'   => ['GET'],
                              ],
                              function(Access $access){
                                  echo $access->owner instanceof Template, 'success';
                              },
                              [function(Access $access){
                                  echo $access->owner instanceof Template, 'fail';
                              }]
                              )
                              ->getSnippet(TestSnippet::className()), -5);
        $this->expectOutputString('1success');
    }


    public function testCheckAccessFalse()
    {
        $this->assertNull(Rock::$app->template
                              ->checkAccess(
                              [
                                  'allow'     => true,
                                  'verbs'   => ['POST'],
                              ],
                              [function(Access $access){
                                  echo $access->owner instanceof Template, 'success';
                              }],
                              [function(Access $access){
                                  echo $access->owner instanceof Template, 'fail';
                              }]
                              )
                     ->getSnippet(TestSnippet::className()));
        $this->expectOutputString('1fail');
    }
}
 