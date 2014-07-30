<?php

namespace rockunit\core\filters\verbs\ContentNegotiatorFilter;

use rock\base\Controller;
use rock\filters\ContentNegotiatorFilter;
use rock\i18n\i18nInterface;
use rock\response\Response;
use rock\Rock;

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

/**
 * @group base
 * @group filters
 */
class ContentNegotiatorFilterTest extends \PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        Response::$format = Response::FORMAT_HTML;
    }

    public function test_()
    {
        $controller = new FooController();
        Response::$data = $controller->method('actionIndex');
        $this->assertSame(Rock::$app->language, i18nInterface::EN);
        Rock::$app->response->send();
        $this->expectOutputString(json_encode(['foo', 'bar']));
    }

    public static function tearDownAfterClass()
    {
        Response::$format = Response::FORMAT_HTML;
    }
}
 