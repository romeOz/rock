<?php

namespace rockunit\filters;

use rock\core\Controller;
use rock\filters\ContentNegotiatorFilter;
use rock\response\Response;
use rock\Rock;

/**
 * @group filters
 */
class ContentNegotiatorFilterTest extends \PHPUnit_Framework_TestCase
{
    public function test_()
    {
        $response = Rock::$app->response;
        $response->isSent = false;
        $config = [
            'response' => $response
        ];
        $controller = new ContentNegotiatorFilterController($config);

        $controller->response->data = $controller->method('actionIndex');
        $this->assertSame('en', Rock::$app->language);
        $this->assertSame($controller->response->format, Response::FORMAT_JSON);
        $controller->response->send();
        $this->expectOutputString(json_encode(['foo', 'bar']));
    }
}


class ContentNegotiatorFilterController extends Controller
{
    public function behaviors()
    {
        return [
            [
                'class' => ContentNegotiatorFilter::className(),
                'only' => ['actionIndex'],  // in a controller
                'response' => $this->response,
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