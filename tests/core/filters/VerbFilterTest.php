<?php

namespace rockunit\core\filters\verbs;

use rock\base\Controller;
use rock\filters\VerbFilter;
use rock\request\Request;

/**
 * @group base
 * @group filters
 */
class VerbFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testSelectActions()
    {
        $controller = new FooController();
        $_POST['_method'] = 'GET';
        $this->assertNull($controller->method('actionIndex'));
        $_POST['_method'] = 'POST';
        $this->assertSame($controller->method('actionIndex'), 'test');
        $_POST['_method'] = 'PUT';
        $this->assertSame($controller->method('actionIndex'), 'test');
    }


    public function testAllActions()
    {
        $controller = new BarController();
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


class FooController extends Controller
{
    public function behaviors()
    {
        return [
            [
                'class' => VerbFilter::className(),
                'actions' => [
                    'actionView'  => [Request::GET],
                    'actionIndex'  => [Request::POST, Request::PUT],
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