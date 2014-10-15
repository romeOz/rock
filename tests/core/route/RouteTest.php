<?php

namespace rockunit\core\route;


use rock\helpers\Helper;
use rock\route\Route;

/**
 * @group base
 * @group route
 */
class RouteTest extends RouteConfigTest
{
    /**
     * @dataProvider providerSuccess
     */
    public function testSuccess($request, $pattern, $verb, $filter = null, $output)
    {
        call_user_func($request);
        $is = (new Route())
            ->success(
                    function (Route $route) {
                        echo 'total_success' . $route->getErrors();
                    }
            )
            ->fail(
                function (Route $route) {
                    echo 'total_fail' . $route->getErrors();
                }
            )
            ->addRoute(
                $verb,
                $pattern,
                function (array $data) {
                    echo Helper::getValue($data['controller']) . 'action';
                },
                $filter
            );
        $this->assertTrue($is);
        $this->expectOutputString($output);
    }


    /**
     * @dataProvider providerFail
     */
    public function testFail($request, $pattern, $verb, $filter = null, $output)
    {
        call_user_func($request);
        $is = (new Route())
            ->success(
                function (Route $route) {
                    echo 'total_success' . $route->getErrors();
                }
            )
            ->fail(
                function (Route $route) {
                    echo 'total_fail' . $route->getErrors();
                }
            )
            ->addRoute(
                $verb,
                $pattern,
                function (array $data) {
                    echo Helper::getValue($data['controller']) . 'action';
                },
                $filter
            );

        $this->assertFalse($is);
        $this->expectOutputString($output);
    }

    /**
     * @dataProvider providerUrlSuccess
     */
    public function testResourceSuccess($url, $verb, $output)
    {
        $_SERVER['HTTP_HOST'] = $_SERVER['SERVER_NAME'] = 'site.com';
        $_SERVER['REQUEST_URI'] = $url;
        $_POST['_method'] = $verb;
        $route = (new Route)
            ->success(
                function (Route $route) {
                    echo 'total_success' . $route->getErrors();
                }
            )->fail(
                function (Route $route) {
                    echo 'total_fail' . $route->getErrors();
                }
            );

        $this->assertTrue($route->REST(
            'orders',
            OrdersController::className(),
            ['only' => ['index', 'show', 'update', 'create', 'delete']]
        ));
        $this->assertSame($route->getErrors(), 0);
        $this->expectOutputString($output);
    }


    /**
     * @dataProvider providerUrlFail
     */
    public function testResourceFail($url, $verb, $errors, $output)
    {
        $_SERVER['HTTP_HOST'] = $_SERVER['SERVER_NAME'] = 'site.com';
        $_SERVER['REQUEST_URI'] = $url;
        $_POST['_method'] = $verb;
        $route = (new Route)
            ->success(
                function (Route $route) {
                    echo 'total_success' . $route->getErrors();
                }
            )
            ->fail(
                function (Route $route) {
                    echo 'total_fail' . $route->getErrors();
                }
            );

        $this->assertFalse($route->REST(
            'orders',
            OrdersController::className(),
            ['only' => ['index', 'show', 'update', 'create', 'delete']]
        ));
        $this->assertSame($route->getErrors(), $errors);
        $this->expectOutputString($output);
    }
}
 