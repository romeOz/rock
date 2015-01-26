<?php

namespace rockunit\core\filters\verbs\AccessFilter;

use rock\access\Access;
use rock\core\Controller;
use rock\filters\AccessFilter;

/**
 * @group base
 * @group filters
 */
class AccessFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testSelectActions()
    {
        $controller = new BarController();
        $this->assertSame($controller->method('actionIndex'), 'index');
        $this->assertNull($controller->actionView());
        $this->expectOutputString('11');
    }

    public function testAllActions()
    {
        $controller = new FooController();
        $this->assertNull($controller->method('actionIndex'));
        $this->assertNull($controller->actionView());
        $this->expectOutputString('1111');
    }


    public function testSuccess()
    {
        $controller = new BazController();
        $this->assertSame($controller->method('actionIndex'), 'index');
        $this->assertSame($controller->actionView(), 'view');
        $this->expectOutputString('success0success0');
    }

    public function testMultiAccess()
    {
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
                        'verbs' => ['PUT'],
                        'roles' => ['editor']
                    ],
                'fail' =>  [function(Access $access){
                    echo $access->isErrorRoles().$access->isErrorIps().$access->isErrorCustom().$access->isErrorVerbs();
                }],
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
                        'verbs' => ['PUT'],
                        'roles' => ['editor']
                    ],
                'fail' =>  function(Access $access){
                    echo $access->isErrorRoles().$access->isErrorIps().$access->isErrorCustom().$access->isErrorVerbs();
                },
                'success' =>  function(Access $access){
                    echo 'success' . $access->getErrors();
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
                        'verbs' => ['PUT'],
                        'roles' => ['editor']
                    ],
                'fail' =>  function(Access $access){
                    echo $access->isErrorRoles().$access->isErrorIps().$access->isErrorCustom().$access->isErrorVerbs();
                },
                'success' =>  function(Access $access){
                    echo 'success' . $access->getErrors();
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
                        'verbs' => ['GET'],
                    ],
            ],
            [
                'class' => AccessFilter::className(),
                'only' => ['actionCreate'],
                'rules' =>
                    [
                        'allow' => true,
                        'verbs' => ['GET'],
                    ],
            ],
            [
                'class' => AccessFilter::className(),
                'only' => ['actionCreate'],
                'rules' =>
                    [
                        'allow' => true,
                        'verbs' => ['POST'],
                    ],
            ],
            [
                'class' => AccessFilter::className(),
                'except' =>  ['actionIndex', 'actionUpdate', 'actionCreate'],
                'rules' =>
                    [
                        'allow' => true,
                        'verbs' => ['POST'],
                    ],
            ],
            [
                'class' => AccessFilter::className(),
                'except' =>  ['actionIndex', 'actionUpdate', 'actionCreate'],
                'rules' =>
                    [
                        'allow' => true,
                        'verbs' => ['GET'],
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
 