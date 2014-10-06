<?php
namespace rockunit\core;

use rock\base\Controller;
use rock\helpers\Json;
use rock\response\Response;
use rock\Rock;
use rock\route\Route;

$_SERVER['HTTP_HOST'] = $_SERVER['SERVER_NAME'] = 'site.com';
$_SERVER['REQUEST_URI'] = '/';
class JsonController extends Controller
{
    public function actionIndex()
    {
        Rock::$app->response->format = Response::FORMAT_JSON;

        return [
            'foo' => 'text',
            'bar'
        ];
    }
}

class XmlController extends Controller
{
    public function actionIndex()
    {
        Rock::$app->response->format = Response::FORMAT_XML;

        return [
            'foo' => 'text',
            'bar'
        ];
    }
}

/**
 * @group base
 */
class ResponseTest extends \PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        Rock::$app->response->format = Response::FORMAT_HTML;
    }

    protected function tearDown()
    {
        Rock::$app->response->isSent = false;
    }


    public function testGetJson()
    {
        (new Route())->get(
            '/',
            function (array $dataRoute) {
                return (new JsonController())->actionIndex();
            }
        );
        Rock::$app->response->send();
        $this->expectOutputString(
            Json::encode(
                [
                    'foo' => 'text',
                    'bar'
                ]
            )
        );
    }


    public function testGetXml()
    {
        (new Route())->get(
            '/',
            function (array $dataRoute) {
                return (new XmlController())->actionIndex();
            }
        );
        Rock::$app->response->send();
        $this->expectOutputString(
            "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<response><foo>text</foo><item>bar</item></response>");
    }

    public static function tearDownAfterClass()
    {
        Rock::$app->response->format = Response::FORMAT_HTML;
    }
}