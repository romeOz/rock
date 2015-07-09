<?php

namespace rockunit\filters;


use rock\core\Controller;
use rock\filters\CSP;
use rock\response\Response;

class CSPTest extends \PHPUnit_Framework_TestCase
{
    public function test_()
    {
        $response = new Response();
        $controller = new CSPController(['response' => $response]);
        $this->assertSame('test', $controller->method('actionIndex'));
        $this->assertEquals("script-src 'self' 'unsafe-eval' site.com; style-src 'self' foo.com;", $response->getHeaders()->get('content-security-policy'));
    }
}

class CSPController extends Controller
{
    public $compare;
    public function behaviors()
    {
        return [
            'csp' => [
                'class' => CSP::className(),
                'response' => $this->response,
                'policy' => [
                    'script-src' => ['self', 'unsafe-eval', 'site.com'],
                    'style-src' =>  "self foo.com"
                ]
            ],

        ];
    }

    public function actionIndex()
    {
        return 'test';
    }
}
