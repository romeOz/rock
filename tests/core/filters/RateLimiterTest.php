<?php

namespace rockunit\core\filters\RateLimiter;

use rock\core\Controller;
use rock\filters\RateLimiter;
use rock\response\Response;
use rock\Rock;

/**
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
        $response = new Response();
        $controller = new RateLimiterController(['response' => $response]);
        $this->assertSame($controller->method('actionIndex'), 'test');
        $this->assertSame($_SESSION['_allowance'][$controller::className().'::actionIndex']["maxRequests"], 1);
        $this->assertSame($controller->method('actionIndex'), 'test');
        $this->assertSame($_SESSION['_allowance'][$controller::className().'::actionIndex']["maxRequests"], 0);
        $this->assertNull($controller->method('actionIndex'));
        $this->assertSame(2, $response->getHeaders()->get('x-rate-limit-limit'));
        $this->assertSame(429, $response->statusCode);
        sleep(4);

        $this->assertSame($controller->method('actionIndex'), 'test');
        $this->assertSame($_SESSION['_allowance'][$controller::className().'::actionIndex']["maxRequests"], 1);


        $controller = new BarController();
        $this->assertSame($controller->method('actionIndex'), 'test');
        $this->assertSame($_SESSION['_allowance'][$controller::className().'::actionIndex']["maxRequests"], 1);
        $this->assertSame($controller->method('actionIndex'), 'test');
        $this->assertSame($_SESSION['_allowance'][$controller::className().'::actionIndex']["maxRequests"], 0);
        $this->assertNull($controller->method('actionIndex'));
        sleep(4);

        $this->assertSame($controller->method('actionIndex'), 'test');
        $this->assertSame($_SESSION['_allowance'][$controller::className().'::actionIndex']["maxRequests"], 1);
    }

    public function testFail()
    {
        $controller = new RateLimiterController();
        $this->assertSame($controller->method('actionView'), 'view');
        $this->assertSame($controller->method('actionView'), 'view');
        $this->assertSame($controller->method('actionView'), 'view');
    }
}


class RateLimiterController extends Controller
{
    public function behaviors()
    {
        return [
            'rateLimiter' => [
                'class' => RateLimiter::className(),
                'actions' => [
                    'actionIndex' => [2, 2]
                ],
                'response' => $this->response
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