<?php

namespace rockunit\core\template\behaviors;

use rock\access\Access;
use rock\base\Snippet;
use rock\event\Event;
use rock\filters\AccessFilter;
use rock\Rock;
use rock\template\Template;


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

        ];
    }

    public function get()
    {
        return 'bar';
    }
}

/**
 * @group base
 */
class SnippetBehaviorsTest extends \PHPUnit_Framework_TestCase {

    public function setUp()
    {
        static::tearDownAfterClass();
    }

    public static function tearDownAfterClass()
    {
        Event::offAll();
    }

    public function testSnippetAccessFalse()
    {
        $result = Rock::$app->template->getSnippet(SnippetAccessFalse::className());
        $this->assertNull($result);
        $this->expectOutputString('1success_11fail_2');
    }

    public function testSnippetAccessTrue()
    {
        $result = Rock::$app->template->getSnippet(SnippetAccessTrue::className());
        $this->assertSame('bar', $result);
        $this->expectOutputString('1success_11success_2');
    }
}
 