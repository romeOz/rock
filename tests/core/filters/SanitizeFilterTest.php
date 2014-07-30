<?php

namespace rockunit\core\filters\verbs\SanitizeFilter;

use rock\base\Controller;
use rock\filters\SanitizeFilter;

class FooController extends Controller
{
    public function behaviors()
    {
        return [
            'filter_1' => [
                'class' => SanitizeFilter::className(),
                'only' => ['actionIndex', 'actionView'],
                'filters' => ['abs']
            ],
            'filter_2' => [
                'class' => SanitizeFilter::className(),
                'only' => ['actionIndex', 'actionView'],
                'filters' => ['round']
            ],

        ];
    }


    public function actionIndex()
    {
        return -5.5;
    }

    public function actionView()
    {
        if ($this->before(__METHOD__) === false) {
            return null;
        }
        $result = -6.5;
        if ($this->after('actionView', $result) === false) {
            return null;
        }

        return $result;
    }

    public function actionUpdate()
    {
        return 'update';
    }
}

/**
 * @group base
 * @group filters
 */
class SanitizeFilterTest extends \PHPUnit_Framework_TestCase
{
    public function test_()
    {
        $controller = new FooController();
        $this->assertSame($controller->method('actionIndex'), 6.0);
        $this->assertSame($controller->actionView(), 7.0);
        $this->assertSame($controller->method('actionUpdate'), 'update');
    }
}
 