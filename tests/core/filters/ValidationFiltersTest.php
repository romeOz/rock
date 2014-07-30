<?php

namespace rockunit\core\filters\verbs\ValidationFilters;

use rock\base\Controller;
use rock\filters\SanitizeFilter;
use rock\filters\ValidationFilters;
use rock\validation\Validation;

class FooController extends Controller
{
    public function behaviors()
    {
        return [
            'filter_1' => [
                'class' => SanitizeFilter::className(),
                'only' => ['actionIndex'],
                'filters' => ['trim']
            ],
            'validation_1' => [
                'class' => ValidationFilters::className(),
                'only' => ['actionIndex', ],
                'validation' => Validation::string()
            ],
            'validation_2' => [
                'class' => ValidationFilters::className(),
                'only' => ['actionView'],
                'validation' => Validation::string()
            ],
        ];
    }


    public function actionIndex()
    {
        return ' index      ';
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

}

/**
 * @group base
 * @group filters
 */
class ValidationFiltersTest extends \PHPUnit_Framework_TestCase
{
    public function testSuccess()
    {
        $controller = new FooController();
        $this->assertSame($controller->method('actionIndex'), 'index');
    }

    public function testFail()
    {
        $controller = new FooController();
        $this->assertNull($controller->actionView());
    }
}
 