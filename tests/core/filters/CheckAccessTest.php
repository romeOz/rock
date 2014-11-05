<?php

namespace rockunit\core\filters;

use rock\access\Access;
use rock\base\Controller;
use rock\event\Event;

class BazController extends Controller
{
    const EVENT_BEGIN_GET = 'beginGet';
    const EVENT_END_GET = 'endGet';
    public $test = 'test';

    public function actionIndex()
    {
        if ($this->beforeAction('foo') === false) {
            return null;
        }
        $result = 'foo';
        if ($this->afterAction('foo', $result) === false) {
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
    //    public function beforeAction()
    //    {
    //
    //        return parent::beforeAction();
    //    }
}

/**
 * @group base
 * @group filters
 */
class CheckAccessTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        static::tearDownAfterClass();
    }

    public static function tearDownAfterClass()
    {
        Event::offAll();
    }

    public function testCheckAccessTrue()
    {
        $controller = (new BazController())
            ->checkAccess(
                [
                    'allow' => true,
                    'verbs' => ['GET'],
                ],
                [
                    function (Access $access) {
                        $this->assertTrue($access->owner instanceof BazController);
                        echo 'success';
                    }
                ],
                [
                    function (Access $access) {
                        $this->assertTrue($access->owner instanceof BazController);
                        echo 'fail';
                    }
                ]
            )
            ->on(
                Controller::EVENT_BEFORE_ACTION,
                function (Event $event) {
                    $this->assertNull($event->result);
                    $this->assertSame('bar', $event->data);
                },
                'bar')
            ->on(
                Controller::EVENT_AFTER_ACTION,
                function (Event $event) {
                    $this->assertSame('foo', $event->result);
                });
        $this->assertEquals('foo', $controller->actionIndex());
        $this->expectOutputString('success');
    }


    public function testCheckAccessFalse()
    {
        $controller = (new BazController())
            ->checkAccess(
                [
                    'allow' => true,
                    'verbs' => ['POST'],
                ],
                [
                    function (Access $access) {
                        $this->assertTrue($access->owner instanceof BazController);
                        echo 'success';
                    }
                ],
                [
                    function (Access $access) {
                        $this->assertTrue($access->owner instanceof BazController);
                        echo 'fail';
                    }
                ]
            )
            ->on(
                Controller::EVENT_BEFORE_ACTION,
                function () {
                    echo Controller::EVENT_BEFORE_ACTION;
                })
            ->on(Controller::EVENT_AFTER_ACTION, function(){
                echo Controller::EVENT_AFTER_ACTION;
            });
        $this->assertNull($controller->actionIndex());
        $this->expectOutputString('fail');
    }


    public function testCheckAccessFalseByMethod()
    {
        $this->assertNull(
            (new BazController())
                ->checkAccess(
                    [
                        'allow' => true,
                        'verbs' => ['POST'],
                    ],
                    [
                        function (Access $access) {
                            $this->assertTrue($access->owner instanceof BazController);
                            echo 'success';
                        }
                    ],
                    [
                        function (Access $access) {
                            $this->assertTrue($access->owner instanceof BazController);
                            echo 'fail';
                        }
                    ]
                )
                ->method('bar')
        );
        $this->expectOutputString('fail');
    }

    public function testCheckAccessIpsTrue()
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $result = (new BazController())
            ->checkAccess(
                [
                    'allow' => true,
                    'ips' => ['127.0.0.1'],
                ],
                [
                    function (Access $access) {
                        $this->assertTrue($access->owner instanceof BazController);
                        echo 'success';
                    }
                ],
                [
                    function (Access $access) {
                        $this->assertTrue($access->owner instanceof BazController);
                        echo 'fail';
                    }
                ]
            )
            ->actionIndex();
        $this->assertEquals('foo', $result);
        $this->expectOutputString('success');
    }

    public function testCheckAccessIpsFalse()
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $this->assertNull(
            (new BazController())
                ->checkAccess(
                    [
                        'allow' => true,
                        'ips' => ['127.0.0.2'],
                    ],
                    [
                        function (Access $access) {
                            $this->assertTrue($access->owner instanceof BazController);
                            echo 'success';
                        }
                    ],
                    [
                        function (Access $access) {
                            $this->assertTrue($access->owner instanceof BazController);
                            echo 'fail';
                        }
                    ]
                )
                ->actionIndex()
        );
        $this->expectOutputString('fail');
    }
} 