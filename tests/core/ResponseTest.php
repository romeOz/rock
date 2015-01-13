<?php
namespace rockunit\core;

use rock\base\Controller;
use rock\helpers\Json;
use rock\helpers\StringHelper;
use rock\response\Response;
use rock\response\ResponseException;
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
        $response = Rock::$app->response;
        $response->format = Response::FORMAT_HTML;
        $response->isSent = false;
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


    public function rightRanges()
    {
        // http://www.w3.org/Protocols/rfc2616/rfc2616.txt
        return [
            ['0-5', '0-5', 6, '12ёж'],
            ['2-', '2-66', 65, 'ёжик3456798áèabcdefghijklmnopqrstuvwxyz!"§$%&/(ёжик)=?'],
            ['-12', '55-66', 12, '(ёжик)=?'],
        ];
    }

    /**
     * @dataProvider rightRanges
     */
    public function testSendFileRanges($rangeHeader, $expectedHeader, $length, $expectedContent)
    {
        /** @var Response $response */
        $response = new Response();
        $dataFile = Rock::getAlias('@rockunit/data/response/data.txt');
        $fullContent = file_get_contents($dataFile);
        $_SERVER['HTTP_RANGE'] = 'bytes=' . $rangeHeader;
        ob_start();
        $response->sendFile($dataFile)->send();
        $content = ob_get_clean();

        $this->assertEquals($expectedContent, $content);
        $this->assertEquals(206, $response->statusCode);
        $headers = $response->headers;
        $this->assertEquals("bytes", $headers->get('Accept-Ranges'));
        $this->assertEquals("bytes " . $expectedHeader . '/' . StringHelper::byteLength($fullContent), $headers->get('Content-Range'));
        $this->assertEquals('text/plain', $headers->get('Content-Type'));
        $this->assertEquals("$length", $headers->get('Content-Length'));
    }

    public function wrongRanges()
    {
        // http://www.w3.org/Protocols/rfc2616/rfc2616.txt
        return [
            ['1-2,3-5,6-10'],	// multiple range request not supported
            ['5-1'],			// last-byte-pos value is less than its first-byte-pos value
            ['-100000'],		// last-byte-pos bigger then content length
            ['10000-'],			// first-byte-pos bigger then content length
        ];
    }

    /**
     * @dataProvider wrongRanges
     */
    public function testSendFileWrongRanges($rangeHeader)
    {
        $this->setExpectedException(ResponseException::className());

        /** @var Response $response */
        $response = new Response();

        $dataFile = Rock::getAlias('@rockunit/data/response/data.txt');
        $_SERVER['HTTP_RANGE'] = 'bytes=' . $rangeHeader;
        $response->sendFile($dataFile);
    }
}