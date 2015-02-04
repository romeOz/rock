<?php

namespace rockunit\core\template\behaviors;

use rock\access\Access;
use rock\events\Event;
use rock\filters\AccessFilter;
use rock\template\Snippet;
use rock\template\Template;

/**
 * @group base
 */
class SnippetBehaviorsTest extends \PHPUnit_Framework_TestCase {

    /** @var  Template */
    protected $template;
    public function setUp()
    {
        static::tearDownAfterClass();
        $config = [
            'snippets' => [
                'snippetAccessFalse' => [
                    'class' => SnippetAccessFalse::className()
                ],
                'snippetAccessTrue' => [
                    'class' => SnippetAccessTrue::className()
                ]
            ]
        ];
        $this->template = new Template($config);
    }

    public static function tearDownAfterClass()
    {
        Event::offAll();
    }

    public function testSnippetAccessFalse()
    {
        $result = $this->template->getSnippet('snippetAccessFalse');
        $this->assertNull($result);
        $this->expectOutputString('1success_11fail_2');
    }

    public function testSnippetAccessTrue()
    {
        $result = $this->template->getSnippet('snippetAccessTrue');
        $this->assertSame('bar', $result);
        $this->expectOutputString('1success_11success_2');
    }
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
                        'ips'   => ['127.0.0.1'],
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
                        'ips' => ['127.0.0.5'],
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
                        'ips'   => ['127.0.0.1'],

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
                        'ips'   => ['127.0.0.5'],
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