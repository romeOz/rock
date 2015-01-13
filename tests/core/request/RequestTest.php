<?php
namespace rockunit\core\request;


use rock\request\Request;
use rock\sanitize\Sanitize;

/**
 * @group base
 * @group request
 */
class RequestTest extends \PHPUnit_Framework_TestCase
{
    public function testScalar()
    {
        $_GET['foo'] = ' <b>foo</b>     ';
        $_GET['bar'] = '    <b>bar   </b>';
        $this->assertEquals('<b>foo</b>', Request::get('foo', null, Sanitize::trim()));
        $this->assertEquals('bar', Request::get('bar'));
    }

    public function testScalarNull()
    {
        $this->assertNull(Request::get('baz'));
    }

    public function testSanitize()
    {
        $_GET['foo'] = ' <b>foo</b>     ';
        $_GET['bar'] = '    <b>bar   </b>';
        $_GET['baz'] = '{"baz" : " <b> baz  </b>     "}';
        $result = Request::getAll(Sanitize::attributes(
            [
                'bar' => Sanitize::removeTags()->trim(),
                'baz' => Sanitize::unserialize()->removeTags()->trim(),
            ]
        ));
        $this->assertEquals(' <b>foo</b>     ', $result['foo']);
        $this->assertEquals('bar', $result['bar']);
        $this->assertEquals(['baz' => 'baz'], $result['baz']);
    }

    public function testAllAttributesTrim()
    {
        $_GET['foo'] = ' <b>foo</b>     ';
        $_GET['bar'] = '    <b>bar   </b>';
        $result = Request::getAll(Sanitize::trim());
        $this->assertEquals($result['foo'], '<b>foo</b>');
        $this->assertEquals($result['bar'], '<b>bar   </b>');
    }


    public function testAllAttributesUnserialize()
    {
        $_GET['foo'] = '{"foo" : "foo"}';
        $_GET['bar'] = '{"bar" : "bar"}';
        $result = Request::getAll(Sanitize::unserialize());
        $this->assertEquals(['foo' => 'foo'], $result['foo']);
        $this->assertEquals(['bar' => 'bar'], $result['bar']);
    }

    public function testUnserialize()
    {
        $_GET['foo'] = ' <b>foo</b>     ';
        $_GET['bar'] = '{"bar" : "bar"}';
        $result = Request::getAll(Sanitize::attributes(['bar' => Sanitize::unserialize()]));
        $this->assertEquals(' <b>foo</b>     ', $result['foo']);
        $this->assertEquals(['bar' => 'bar'], $result['bar']);
    }

    public function testNumeric()
    {
        $_GET['foo'] = '<b>-5.5</b>';
        $_GET['bar'] = '5.5';
        $_GET['baz'] = '{"baz" : "5.6"}';
        $result = Request::getAll(Sanitize::attributes(
            [
                'foo' => Sanitize::call('strip_tags')->call('abs')->call('ceil'),
                'bar' => Sanitize::call('floor'),
                'baz' => Sanitize::unserialize()->call('round'),
            ]
        ));
        $this->assertEquals(6, $result['foo']);
        $this->assertEquals(5, $result['bar']);
        $this->assertEquals(['baz' => 6], $result['baz']);
    }

    public function testPost()
    {
        $_POST['foo'] = '<b>foo</b>    ';
        $_POST['bar'] = ['foo' => ['  <b>foo</b>'], 'bar' => '{"baz" : "<b>bar</b>baz "}'];
        $_POST['baz'] = '{"foo" : "<b>foo</b>", "bar" : {"foo" : "<b>baz</b>   "}}';
        $_POST['test'] = serialize(['foo' => ['  <b>foo</b>'], 'bar' => '<b>bar</b>baz ']);
        $result = Request::postAll(Sanitize::allOf(Sanitize::unserialize()->removeTags()->trim()));
        $this->assertEquals('foo', $result['foo']);
        $this->assertEquals(['foo' => ['foo'], 'bar' => ['baz'=>'barbaz']], $result['bar']);
        $this->assertEquals(['foo' => 'foo', 'bar' => ['foo' => 'baz']],$result['baz']);
        $this->assertEquals(['foo' => ['foo'], 'bar' => 'barbaz'], $result['test']);
    }


    public function testParseAcceptHeader()
    {
        $request = new Request;

        $this->assertEquals([], $request->parseAcceptHeader(' '));

        $this->assertEquals([
                                'audio/basic' => ['q' => 1],
                                'audio/*' => ['q' => 0.2],
                            ], $request->parseAcceptHeader('audio/*; q=0.2, audio/basic'));

        $this->assertEquals([
                                'application/json' => ['q' => 1, 'version' => '1.0'],
                                'application/xml' => ['q' => 1, 'version' => '2.0', 'x'],
                                'text/x-c' => ['q' => 1],
                                'text/x-dvi' => ['q' => 0.8],
                                'text/plain' => ['q' => 0.5],
                            ], $request->parseAcceptHeader('text/plain; q=0.5,
            application/json; version=1.0,
            application/xml; version=2.0; x,
            text/x-dvi; q=0.8, text/x-c'));
    }

    public function testPreferredLanguage()
    {
        $request = new Request();
        $request->acceptableLanguages = [];
        $this->assertEquals('en', $request->getPreferredLanguage());

        $request = new Request();
        $request->acceptableLanguages = ['de'];
        $this->assertEquals('en', $request->getPreferredLanguage());

        $request = new Request();
        $request->acceptableLanguages = ['en-us', 'de', 'ru-RU'];
        $this->assertEquals('en', $request->getPreferredLanguage(['en']));

        $request = new Request();
        $request->acceptableLanguages = ['en-us', 'de', 'ru-RU'];
        $this->assertEquals('de', $request->getPreferredLanguage(['ru', 'de']));
        $this->assertEquals('de-DE', $request->getPreferredLanguage(['ru', 'de-DE']));

        $request = new Request();
        $request->acceptableLanguages = ['en-us', 'de', 'ru-RU'];
        $this->assertEquals('de', $request->getPreferredLanguage(['de', 'ru']));

        $request = new Request();
        $request->acceptableLanguages = ['en-us', 'de', 'ru-RU'];
        $this->assertEquals('ru-ru', $request->getPreferredLanguage(['ru-ru']));

        $request = new Request();
        $request->acceptableLanguages = ['en-us', 'de'];
        $this->assertEquals('ru-ru', $request->getPreferredLanguage(['ru-ru', 'pl']));
        $this->assertEquals('ru-RU', $request->getPreferredLanguage(['ru-RU', 'pl']));

        $request = new Request();
        $request->acceptableLanguages = ['en-us', 'de'];
        $this->assertEquals('pl', $request->getPreferredLanguage(['pl', 'ru-ru']));
    }
}