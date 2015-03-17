<?php

namespace rockunit\core\filters;


use rock\core\Controller;
use rock\csrf\CSRF;
use rock\filters\CsrfFilter;
use rock\response\Response;

class CsrfFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testFail()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $this->assertNull((new CsrfController())->method('actionIndex'));

        $csrf = new CSRF();
        $_POST[$csrf->csrfParam] = 'fail';
        $this->assertNull((new CsrfController())->method('actionIndex'));

        $response = new Response();
        $controller = new CsrfController(['response' => $response]);
        $controller->compare = 'fail';
        $this->assertNull($controller->method('actionIndex'));
        $this->assertSame($csrf->get(), $response->getHeaders()->get(CSRF::CSRF_HEADER));
        $this->assertSame(403, $response->statusCode);
    }

    /**
     * @depends testFail
     */
    public function testSuccess()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $this->assertSame('test', (new CsrfController())->method('actionIndex'));

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $csrf = new CSRF();

        $controller = new CsrfController();
        $controller->compare = $csrf->get();
        $this->assertSame('test', $controller->method('actionIndex'));

        $_POST[$csrf->csrfParam] = $csrf->get();
        $this->assertSame('test', (new CsrfController())->method('actionIndex'));
    }
}


class CsrfController extends Controller
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