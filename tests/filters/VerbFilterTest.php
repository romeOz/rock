<?php

namespace rockunit\filters;

use rock\core\Controller;
use rock\filters\VerbFilter;
use rock\request\Request;
use rock\response\Response;

/**
 * @group filters
 */
class VerbFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testSelectActions()
    {
        $response = new Response();
        $controller = new VerbFilterController(['response' => $response]);
        $_POST['_method'] = 'GET';
        $this->assertNull($controller->method('actionIndex'));
        $this->assertSame(405, $response->statusCode);
        $this->assertSame('POST, PUT', $response->getHeaders()->get('allow'));

        $controller = new VerbFilterController(['response' => $response]);
        $_POST['_method'] = 'POST';
        $this->assertSame($controller->method('actionIndex'), 'test');
        $_POST['_method'] = 'PUT';
        $this->assertSame($controller->method('actionIndex'), 'test');
    }

    public function testAllActions()
    {
        $controller = new VerbFilter2Controller();
        $_POST['_method'] = 'GET';
        $this->assertNull($controller->method('actionIndex'));
        $this->assertNull($controller->method('actionView'));
        $this->assertNull($controller->actionFoo());
        $_POST['_method'] = 'POST';
        $this->assertSame($controller->method('actionIndex'), 'test');
        $this->assertSame($controller->method('actionView'), 'view');
        $this->assertSame($controller->actionFoo(), 'foo');
        $_POST['_method'] = 'PUT';
        $this->assertSame($controller->method('actionIndex'), 'test');
        $this->assertSame($controller->method('actionView'), 'view');
        $this->assertSame($controller->actionFoo(), 'foo');
    }
}


class VerbFilterController extends Controller
{
    public function behaviors()
    {
        return [
            [
                'class' => VerbFilter::className(),
                'actions' => [
                    'actionView'  => [Request::GET],
                    'actionIndex'  => [Request::POST, Request::PUT],
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


class VerbFilter2Controller extends Controller
{
    public function behaviors()
    {
        return [
            [
                'class' => VerbFilter::className(),
                'actions' => [
                    '*'  => [Request::POST, Request::PUT],
                ]
            ],
        ];
    }

    public function actionIndex()
    {
        return 'test';
    }

    public function actionFoo()
    {
        if (!$this->beforeAction('actionFoo')) {
            return null;
        }
        return 'foo';
    }

    public function actionView()
    {
        return 'view';
    }
}