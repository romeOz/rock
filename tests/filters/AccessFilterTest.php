<?php

namespace rockunit\filters;

use rock\core\Controller;
use rock\filters\AccessFilter;

/**
 * @group filters
 */
class AccessFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testSelectActions()
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $controller = new BarController();
        $this->assertSame($controller->method('actionIndex'), 'index');
        $this->assertNull($controller->actionView());
        $this->expectOutputString('11');
    }

    public function testAllActions()
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $controller = new FooController();
        $this->assertNull($controller->method('actionIndex'));
        $this->assertNull($controller->actionView());
        $this->expectOutputString('1111');
    }


    public function testSuccess()
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $controller = new BazController();
        $this->assertSame($controller->method('actionIndex'), 'index');
        $this->assertSame($controller->actionView(), 'view');
        $this->expectOutputString('success0success0');
    }

    public function testMultiAccess()
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $controller = new MultiAccessController();
        $this->assertNull($controller->method('actionIndex'));
        $this->assertNull($controller->method('actionUpdate'));
        $this->assertNull($controller->actionView());
        $this->assertSame($controller->actionCreate(), 'create');
    }
}

class FooController extends Controller
{
    public function behaviors()
    {

        return [
            'access' => [
                'class' => AccessFilter::className(),
                'rules' =>
                    [
                        'allow' => true,
                        'ips' => ['127.0.0.5'],
                        'roles' => ['editor']
                    ],
                'fail' =>  function(AccessFilter $access){
                    echo $access->access->isErrorRoles().$access->access->isErrorIps().$access->access->isErrorCustom().$access->access->isErrorVerbs();
                },
            ],
        ];
    }

    public function actionIndex()
    {
        return 'index';
    }

    public function actionView()
    {
        if ($this->beforeAction('actionView') === false) {
            return null;
        }
        $result = 'view';
        if ($this->afterAction('actionView', $result) === false) {
            return null;
        }

        return $result;
    }
}


class BarController extends Controller
{
    public function behaviors()
    {

        return [
            'access' => [
                'class' => AccessFilter::className(),
                'only' => ['actionView'],
                'rules' =>
                    [
                        'allow' => true,
                        'ips' => ['127.0.0.3'],
                        'roles' => ['editor']
                    ],
                'fail' =>  function(AccessFilter $access){
                    echo $access->access->isErrorRoles().$access->access->isErrorIps().$access->access->isErrorCustom().$access->access->isErrorVerbs();
                },
                'success' =>  function(AccessFilter $access){
                    echo 'success' . $access->access->getErrors();
                },
            ],
        ];
    }

    public function actionIndex()
    {
        return 'index';
    }

    public function actionView()
    {
        if ($this->beforeAction('actionView') === false) {
            return null;
        }
        $result = 'view';
        if ($this->afterAction('actionView', $result) === false) {
            return null;
        }

        return $result;
    }
}

class BazController extends Controller
{
    public function behaviors()
    {

        return [
            'access' => [
                'class' => AccessFilter::className(),
                'rules' =>
                    [
                        'allow' => false,
                        'ips' => ['127.0.0.3'],
                        'roles' => ['editor']
                    ],
                'fail' =>  function(AccessFilter $access){
                    echo $access->access->isErrorRoles().$access->access->isErrorIps().$access->access->isErrorCustom().$access->access->isErrorVerbs();
                },
                'success' =>  function(AccessFilter $access){
                    echo 'success' . $access->access->getErrors();
                },
            ],
        ];
    }

    public function actionIndex()
    {
        return 'index';
    }

    public function actionView()
    {
        if ($this->beforeAction('actionView') === false) {
            return null;
        }
        $result = 'view';
        if ($this->afterAction('actionView', $result) === false) {
            return null;
        }

        return $result;
    }
}


class MultiAccessController extends Controller
{
    public function behaviors()
    {
        return [
            [
                'class' => AccessFilter::className(),
                'only' => ['actionIndex', 'actionUpdate'],
                'rules' =>
                    [
                        'allow' => false,
                        'ips' => ['127.0.0.1'],
                    ],
            ],
            [
                'class' => AccessFilter::className(),
                'only' => ['actionCreate'],
                'rules' =>
                    [
                        'allow' => true,
                        'ips' => ['127.0.0.1'],
                    ],
            ],
            [
                'class' => AccessFilter::className(),
                'only' => ['actionCreate'],
                'rules' =>
                    [
                        'allow' => true,
                        'ips' => ['127.0.0.3'],
                    ],
            ],
            [
                'class' => AccessFilter::className(),
                'except' =>  ['actionIndex', 'actionUpdate', 'actionCreate'],
                'rules' =>
                    [
                        'allow' => true,
                        'ips' => ['127.0.0.3'],
                    ],
            ],
            [
                'class' => AccessFilter::className(),
                'except' =>  ['actionIndex', 'actionUpdate', 'actionCreate'],
                'rules' =>
                    [
                        'allow' => true,
                        'ips' => ['127.0.0.3'],
                    ],
            ],

        ];
    }

    public function actionIndex()
    {
        return 'index';
    }

    public function actionUpdate()
    {
        return 'update';
    }

    public function actionView()
    {
        if ($this->beforeAction('actionView') === false) {
            return null;
        }
        $result = 'view';
        if ($this->afterAction('actionView', $result) === false) {
            return null;
        }

        return $result;
    }

    public function actionCreate()
    {
        return 'create';
    }
}
 