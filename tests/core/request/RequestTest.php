<?php
namespace rockunit\core\request;


use rock\request\Request;
use rock\Rock;

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
        $this->assertEquals(Request::get('foo', null, ['trim']), '<b>foo</b>');
        $this->assertEquals(Request::get('bar'), 'bar');
    }


    public function testScalarNull()
    {
        $this->assertNull(Request::get('baz'));
    }

    public function testSetFilter()
    {
        $_GET['foo'] = ' <b>foo</b>     ';
        $_GET['bar'] = '    <b>bar   </b>';
        $_GET['baz'] = '{"baz" : " <b> baz  </b>     "}';
        $result = Request::getAll([
                                                'bar' => [Request::STRIP_TAGS, 'trim'],
                                                'baz' => ['unserialize', Request::STRIP_TAGS, 'trim'],
                                            ]);
        $this->assertEquals($result['foo'], ' <b>foo</b>     ');
        $this->assertEquals($result['bar'], 'bar');
        $this->assertEquals($result['baz'], ['baz' => 'baz']);
    }

    public function testAllTrim()
    {
        $_GET['foo'] = ' <b>foo</b>     ';
        $_GET['bar'] = '    <b>bar   </b>';
        $result = Request::getAll(['trim']);
        $this->assertEquals($result['foo'], '<b>foo</b>');
        $this->assertEquals($result['bar'], '<b>bar   </b>');
    }


    public function testAllUnserialize()
    {
        $_GET['foo'] = '{"foo" : "foo"}';
        $_GET['bar'] = '{"bar" : "bar"}';
        $result = Request::getAll(['unserialize']);
        $this->assertEquals($result['foo'], ['foo' => 'foo']);
        $this->assertEquals($result['bar'], ['bar' => 'bar']);
    }

    public function testUnserialize()
    {
        $_GET['foo'] = ' <b>foo</b>     ';
        $_GET['bar'] = '{"bar" : "bar"}';
        $result = Request::getAll(['bar' => ['unserialize']]);
        $this->assertEquals($result['foo'], ' <b>foo</b>     ');
        $this->assertEquals($result['bar'], ['bar' => 'bar']);
    }

    public function testNumeric()
    {
        $_GET['foo'] = '-5.5</b>     ';
        $_GET['bar'] = '5.5';
        $_GET['baz'] = '{"baz" : "5.6"}';
        $result = Request::getAll([
                                                'foo' => ['abs', 'ceil'],
                                                'bar' => ['floor'],
                                                'baz' => ['unserialize', 'round'],
                                            ]);
        $this->assertEquals($result['foo'], 6);
        $this->assertEquals($result['bar'], 5);
        $this->assertEquals($result['baz'], ['baz' => 6]);
    }


    public function testPost()
    {
        $_POST['foo'] = '<b>foo</b>    ';
        $_POST['bar'] = ['foo' => ['  <b>foo</b>'], 'bar' => '{"baz" : "<b>bar</b>baz "}'];
        $_POST['baz'] = '{"foo" : "<b>foo</b>", "bar" : {"foo" : "<b>baz</b>   "}}';
        $_POST['test'] = serialize(['foo' => ['  <b>foo</b>'], 'bar' => '<b>bar</b>baz ']);
        $result = Request::postAll([Request::UNSERIALIZE, Request::STRIP_TAGS, 'trim']);
        $this->assertEquals($result['foo'], 'foo');
        $this->assertEquals($result['bar'], ['foo' => ['foo'], 'bar' => ['baz'=>'barbaz']]);
        $this->assertEquals($result['baz'], ['foo' => 'foo', 'bar' => ['foo' => 'baz']]);
        $this->assertEquals($result['test'], ['foo' => ['foo'], 'bar' => 'barbaz']);
    }
}
 