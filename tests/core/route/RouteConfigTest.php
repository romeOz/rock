<?php

namespace rockunit\core\route;

use rock\access\Access;
use rock\base\Controller;
use rock\filters\AccessFilter;
use rock\helpers\Helper;
use rock\route\Route;

class OrdersController extends Controller
{
    public function actionIndex()
    {
        echo 'index';
    }
    public function actionShow()
    {
        echo 'show';
    }
    public function actionCreate()
    {
        echo 'create';
    }
    public function actionUpdate()
    {
        echo 'update';
    }

    public function actionDelete()
    {
        echo 'delete';
    }

}

/**
 * @group base
 * @group route
 */
class RouteConfigTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider providerSuccess
     */
    public function testSuccess($request, $pattern, $verb, $filter = null, $output)
    {
        call_user_func($request);
        (new Route(

            [
                'rules' =>
                    [
                        [
                            $verb,
                            $pattern,
                            function (array $data) {
                                echo Helper::getValue($data['controller']) . 'action';
                            },
                            $filter
                        ],
                    ],
                'success' => [
                    function (Route $route) {
                        echo 'total_success' . $route->getErrors();
                    }
                ],
                'fail' => [
                    function (Route $route) {
                        echo 'total_fail' . $route->getErrors();
                    }
                ]
            ]

        ))
        ->run();

        $this->expectOutputString($output);
    }


    public function providerSuccess()
    {
        return [
            [
                function(){
                    $_SERVER['HTTP_HOST'] = $_SERVER['SERVER_NAME'] = 'site.com';
                    $_SERVER['REQUEST_URI'] = '/';
                    $_POST['_method'] = null;
                },
                '/',
                Route::GET,
                null,
                'total_success0action'
            ],
            [
                function(){
                    $_SERVER['HTTP_HOST'] = $_SERVER['SERVER_NAME'] = 'site.com';
                    $_SERVER['REQUEST_URI'] = '/';
                    $_POST['_method'] = Route::PUT;
                },
                '/',
                [Route::PUT],
                null,
                'total_success0action'
            ],
            [
                function(){
                    $_SERVER['HTTP_HOST'] = $_SERVER['SERVER_NAME'] = 'site.com';
                    $_SERVER['REQUEST_URI'] = '/';
                    $_POST['_method'] = Route::PUT;
                },
                '/',
                Route::ANY,
                null,
                'total_success0action'
            ],
            [

                function(){
                    $_SERVER['HTTP_HOST'] = $_SERVER['SERVER_NAME'] = 'site.com';
                    $_SERVER['REQUEST_URI'] = '/news/';
                    $_POST['_method'] = null;
                },
                '~/^
                                 \/
                                 (?P<controller>(news|tags))
                                 \/
                            $/ix',
                Route::GET,
                null,
                'total_success0newsaction'
            ],
            [
                function(){
                    $_SERVER['HTTP_HOST'] = $_SERVER['SERVER_NAME'] = 'admin.site.com';
                    $_SERVER['REQUEST_URI'] = '/news/';
                    $_POST['_method'] = null;
                },
                [
                    Route::FORMAT_HOST => 'admin.site.com',
                    Route::FORMAT_PATH => '~/^
                                 \/
                                 (?P<controller>(news|tags))
                                 \/
                            $/ix'
                ],
                Route::GET,
                null,
                'total_success0newsaction'
            ],

            [
                function(){
                    $_SERVER['HTTP_HOST'] = $_SERVER['SERVER_NAME'] = 'site.com';
                    $_SERVER['REQUEST_URI'] = '/';
                    $_SERVER['REMOTE_ADDR'] = '10.2.3';
                    $_POST['_method'] = null;
                },
                '/',
                Route::GET,
                [
                    'access' => [
                        'class' => AccessFilter::className(),
                        'rules' =>            [
                            'allow' => true,
                            'verbs' => ['GET'],
                            'ips' => ['10.2.3']
                        ],
                        'success' => function (Access $access) {
                            echo 'success_behavior';
                        },
                        'fail' => function (Access $access) {
                            echo $access->getErrors();
                        }
                    ]
                ],
                'success_behaviortotal_success0action'
            ],

            [
                function(){
                    $_SERVER['HTTP_HOST'] = $_SERVER['SERVER_NAME'] = 'site.com';
                    $_SERVER['REQUEST_URI'] = '/';
                    $_SERVER['REMOTE_ADDR'] = '10.2.3';
                    $_POST['_method'] = null;
                },
                '/',
                Route::GET,
                function(){
                    return true;
                },
                'total_success0action'
            ],
        ];
    }


    /**
     * @dataProvider providerFail
     */
    public function testFail($request, $pattern, $verb, $filter = null, $output)
    {
        call_user_func($request);
        (new Route(

            [
                'rules' =>
                    [
                        [
                            $verb,
                            $pattern,
                            function (array $data) {
                                echo Helper::getValue($data['controller']) . 'action';
                            },
                            $filter
                        ],
                    ],
                'success' => [
                    function (Route $route) {
                        echo 'total_success' . $route->getErrors();
                    }
                ],
                'fail' => [
                    function (Route $route) {
                        echo 'total_fail' . $route->getErrors();
                    }
                ]
            ]

        ))
            ->run();

        $this->expectOutputString($output);
    }

    public function providerFail()
    {
        return [
            [
                function(){
                    $_SERVER['HTTP_HOST'] = $_SERVER['SERVER_NAME'] = 'site.com';
                    $_SERVER['REQUEST_URI'] = '/vv';
                    $_POST['_method'] = null;
                },
                '/',
                Route::GET,
                null,
                'total_fail'.Route::E_NOT_FOUND
            ],
            [
                function(){
                    $_SERVER['HTTP_HOST'] = $_SERVER['SERVER_NAME'] = 'site.com';
                    $_SERVER['REQUEST_URI'] = '/';
                    $_POST['_method'] = Route::GET;
                },
                '/',
                [Route::PUT],
                null,
                'total_fail' .Route::E_VERBS
            ],
            [

                function(){
                    $_SERVER['HTTP_HOST'] = $_SERVER['SERVER_NAME'] = 'site.com';
                    $_SERVER['REQUEST_URI'] = '/foo/';
                    $_POST['_method'] = null;
                },
                '~/^
                                 \/
                                 (?P<controller>(news|tags))
                                 \/
                            $/ix',
                Route::GET,
                null,
                'total_fail'.Route::E_NOT_FOUND
            ],
            [
                function(){
                    $_SERVER['HTTP_HOST'] = $_SERVER['SERVER_NAME'] = 'site.com';
                    $_SERVER['REQUEST_URI'] = '/news/';
                    $_POST['_method'] = null;
                },
                [
                    Route::FORMAT_HOST => 'admin.site.com',
                    Route::FORMAT_PATH => '~/^
                                 \/
                                 (?P<controller>(news|tags))
                                 \/
                            $/ix'
                ],
                Route::GET,
                null,
                'total_fail'.Route::E_NOT_FOUND
            ],

            [
                function(){
                    $_SERVER['HTTP_HOST'] = $_SERVER['SERVER_NAME'] = 'site.com';
                    $_SERVER['REQUEST_URI'] = '/';
                    $_SERVER['REMOTE_ADDR'] = '10.2.3';
                    $_POST['_method'] = null;
                },
                '/',
                Route::GET,
                [
                    'access' => [
                        'class' => AccessFilter::className(),
                        'rules' =>
                            [
                                'allow' => false,
                                'verbs' => ['GET'],
                                'ips' => ['10.2.3']
                            ],
                        'success' => [
                            function (Access $access) {
                                echo 'success_behavior' . $access->getErrors();
                            }
                        ],
                        'fail' => [
                            function (Access $access) {
                                echo 'fail_behavior' . $access->getErrors();
                            }
                        ],
                    ]
                ],
                'fail_behavior' . (Route::E_IPS | Route::E_VERBS) . 'total_fail' .
                (Route::E_IPS | Route::E_VERBS)
            ],

            [
                function(){
                    $_SERVER['HTTP_HOST'] = $_SERVER['SERVER_NAME'] = 'site.com';
                    $_SERVER['REQUEST_URI'] = '/';
                    $_SERVER['REMOTE_ADDR'] = '10.2.3';
                    $_POST['_method'] = null;
                },
                '/',
                Route::GET,
                function(){
                    return false;
                },
                'total_fail' . Route::E_NOT_FOUND
            ],
        ];
    }

    public function testMultiRulesSuccess()
    {

        $_SERVER['HTTP_HOST'] = $_SERVER['SERVER_NAME'] = 'site.com';
        $_SERVER['REQUEST_URI'] = '/';

        (new Route(

            [
                'rules' =>
                    [
                        [
                            Route::GET,
                            '/news/',
                            function (array $data) {
                                echo 'action1';
                            }
                        ],
                        [
                            Route::POST,
                            '/',
                            function (array $data) {
                                echo 'action2';
                            }
                        ],
                        [
                            Route::GET,
                            '/',
                            function (array $data) {
                                echo 'action3';
                            }
                        ],
                    ],
                'success' => [
                    function (Route $route) {
                        echo 'total_success' . $route->getErrors();
                    }
                ],
                'fail' => [
                    function (Route $route) {
                        echo 'total_fail' . $route->getErrors();
                    }
                ]
            ]

        ))
            ->run();

        $this->expectOutputString('total_success0action3');
    }



    public function testMultiRulesFail()
    {

        $_SERVER['HTTP_HOST'] = $_SERVER['SERVER_NAME'] = 'site.com';
        $_SERVER['REQUEST_URI'] = '/';

        (new Route(

            [
                'rules' =>
                    [
                        [
                            Route::GET,
                            '/news/',
                            function (array $data) {
                                echo 'action1';
                            }
                        ],
                        [
                            Route::POST,
                            '/',
                            function (array $data) {
                                echo 'action2';
                            }
                        ],
                    ],
                'success' => [
                    function (Route $route) {
                        echo 'total_success' . $route->getErrors();
                    }
                ],
                'fail' => [
                    function (Route $route) {
                        echo 'total_fail' . $route->getErrors();
                    }
                ]
            ]

        ))
            ->run();

        $this->expectOutputString('total_fail'. (Route::E_VERBS | Route::E_NOT_FOUND));
    }


    /**
     * @dataProvider providerUrlSuccess
     */
    public function testResourceSuccess($url, $verb, $output)
    {
        $_SERVER['HTTP_HOST'] = $_SERVER['SERVER_NAME'] = 'site.com';
        $_SERVER['REQUEST_URI'] = $url;
        $_POST['_method'] = $verb;

        $route = new Route(
            [
                'rules' =>
                    [
                        [
                            Route::REST,
                            'orders',
                            OrdersController::className(),
                            ['only' => ['index', 'show', 'update', 'create','delete']]
                        ],
                    ],
                'success' => [
                    function (Route $route) {
                        echo 'total_success' . $route->getErrors();
                    }
                ],
                'fail' => [
                    function (Route $route) {
                        echo 'total_fail' . $route->getErrors();
                    }
                ]
            ]
        );

        $route->run();
        $this->assertSame($route->getErrors(), 0);
        $this->expectOutputString($output);
    }

    public function providerUrlSuccess()
    {
        return [
            ['/orders/', null, 'total_success0index'],
            ['/orders/77', 'PUT', 'total_success0update'],
            ['/orders/77', 'PATCH', 'total_success0update'],
            ['/orders/77', null, 'total_success0show'],
            ['/orders/create/', null, 'total_success0create'],
        ];
    }


    /**
     * @dataProvider providerUrlFail
     */
    public function testResourceFail($url, $verb, $errors, $output)
    {
        $_SERVER['HTTP_HOST'] = $_SERVER['SERVER_NAME'] = 'site.com';
        $_SERVER['REQUEST_URI'] = $url;
        $_POST['_method'] = $verb;

        $route = new Route(
            [
                'rules' =>
                    [
                        [
                            Route::REST,
                            'orders',
                            OrdersController::className(),
                            ['only' => ['index', 'show', 'update', 'create','delete']]
                        ],
//                        [
//                            Route::GET,
//                            '*',
//                            function (array $dataRoute) {
//                            }
//                        ],
                    ],
                'success' => [
                    function (Route $route) {
                        echo 'total_success' . $route->getErrors();
                    }
                ],
                'fail' => [
                    function (Route $route) {
                        echo 'total_fail' . $route->getErrors();
                    }
                ]
            ]
        );

        $route->run();

        $this->assertSame($route->getErrors(), $errors);
        $this->expectOutputString($output);
    }

    public function providerUrlFail()
    {
        return [
            ['/orders', null, 32, 'total_fail32'],
            ['/orders/', 'PUT', 33, 'total_fail33'],
            ['/orders/77/', 'PUT', 32, 'total_fail32'],
            ['/orders/77', 'POST', 33, 'total_fail33'],
            ['/orders/77/', null, 32, 'total_fail32'],
            ['/orders/create/77', null, 32, 'total_fail32'],
        ];
    }
}
 