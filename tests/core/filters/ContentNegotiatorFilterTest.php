<?php

namespace rockunit\core\filters\verbs\ContentNegotiatorFilter;

use rock\core\Controller;
use rock\filters\ContentNegotiatorFilter;
use rock\response\Response;
use rock\Rock;

/**
 * @group filters
 */
class ContentNegotiatorFilterTest extends \PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        Rock::$app->response->format = Response::FORMAT_HTML;
    }

    protected function tearDown()
    {
        Rock::$app->response->isSent = false;
    }

    public function test_()
    {
        $controller = new FooController();
        Rock::$app->response->data = $controller->method('actionIndex');
        $this->assertSame(Rock::$app->language, 'en');
        Rock::$app->response->send();
        $this->expectOutputString(json_encode(['foo', 'bar']));
    }

    public static function tearDownAfterClass()
    {
        Rock::$app->response->format = Response::FORMAT_HTML;
    }
}


class FooController extends Controller
{
    public function behaviors()
    {
        return [
            [
                'class' => ContentNegotiatorFilter::className(),
                'only' => ['actionIndex'],  // in a controller
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,
                ],
                'languages' => [
                    'en',
                    'de',
                ],
            ],

        ];
    }

    public function actionIndex()
    {
        return ['foo', 'bar'];
    }

    public function actionView()
    {
        return 'view';
    }
}