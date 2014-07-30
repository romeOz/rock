<?php

namespace rockunit\core\filters\RateLimiter;

use rock\base\Controller;
use rock\filters\RateLimiter;
use rock\Rock;

class FooController extends Controller
{
    public function behaviors()
    {
        return [
            'rateLimiter' => [
                'class' => RateLimiter::className(),
                'actions' => [
                    'actionIndex' => [2, 2]
                ]
            ],

        ];
    }

    public function actionIndex()
    {
        return 'test';
    }

    public function actionView()
    {
        return 'view';
    }
}


class BarController extends Controller
{
    public function behaviors()
    {
        return [
            'rateLimiter' => [
                'class' => RateLimiter::className(),
                'actions' => [
                    'actionView' => [2, 5],
                    '*' => [2, 2]
                ]
            ],

        ];
    }

    public function actionIndex()
    {
        return 'test';
    }
}

/**
 * @group base
 * @group filters
 */
class RateLimiterTest extends \PHPUnit_Framework_TestCase
{
    public static $session = [];
    public static $cookie = [];

    public function setUp()
    {
        $_SESSION = static::$session;
        $_COOKIE = static::$cookie;
        Rock::$app->cookie->removeAll();
        Rock::$app->session->removeAll();
    }

    public function tearDown()
    {
        static::$session = $_SESSION;
        static::$cookie = $_COOKIE;
    }

    public function testSuccess()
    {
        $controller = new FooController();
        $this->assertSame($controller->method('actionIndex'), 'test');
        $this->assertSame($_SESSION['user']['_allowance'][$controller::className().'::actionIndex']["maxRequests"], 1);
        $this->assertSame($controller->method('actionIndex'), 'test');
        $this->assertSame($_SESSION['user']['_allowance'][$controller::className().'::actionIndex']["maxRequests"], 0);
        $this->assertNull($controller->method('actionIndex'));
        sleep(3);

        $this->assertSame($controller->method('actionIndex'), 'test');
        $this->assertSame($_SESSION['user']['_allowance'][$controller::className().'::actionIndex']["maxRequests"], 1);


        $controller = new BarController();
        $this->assertSame($controller->method('actionIndex'), 'test');
        $this->assertSame($_SESSION['user']['_allowance'][$controller::className().'::actionIndex']["maxRequests"], 1);
        $this->assertSame($controller->method('actionIndex'), 'test');
        $this->assertSame($_SESSION['user']['_allowance'][$controller::className().'::actionIndex']["maxRequests"], 0);
        $this->assertNull($controller->method('actionIndex'));
        sleep(3);

        $this->assertSame($controller->method('actionIndex'), 'test');
        $this->assertSame($_SESSION['user']['_allowance'][$controller::className().'::actionIndex']["maxRequests"], 1);
    }

    public function testFail()
    {
        $controller = new FooController();
        $this->assertSame($controller->method('actionView'), 'view');
        $this->assertSame($controller->method('actionView'), 'view');
        $this->assertSame($controller->method('actionView'), 'view');
    }
}
 