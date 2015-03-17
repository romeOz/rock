<?php

namespace rockunit\core\filters;


use rock\core\Controller;
use rock\csrf\CSRF;
use rock\filters\CsrfFilter;

class CsrfFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testFail()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $this->assertNull((new FooController())->method('actionIndex'));

        $csrf = new CSRF();
        $_POST[$csrf->csrfParam] = 'fail';
        $this->assertNull((new FooController())->method('actionIndex'));

        $controller = new FooController();
        $controller->compare = 'fail';
        $this->assertNull($controller->method('actionIndex'));
    }

    /**
     * @depends testFail
     */
    public function testSuccess()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $this->assertSame('test', (new FooController())->method('actionIndex'));

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $csrf = new CSRF();

        $controller = new FooController();
        $controller->compare = $csrf->get();
        $this->assertSame('test', $controller->method('actionIndex'));

        $_POST[$csrf->csrfParam] = $csrf->get();
        $this->assertSame('test', (new FooController())->method('actionIndex'));
    }
}


class FooController extends Controller
{
    public $compare;
    public function behaviors()
    {
        return [
            'csrfFilter' => [
                'class' => CsrfFilter::className(),
                'response' => $this->response,
                'compare' => $this->compare
            ],

        ];
    }

    public function actionIndex()
    {
        return 'test';
    }
}