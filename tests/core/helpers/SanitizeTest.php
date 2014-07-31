<?php

namespace rockunit\core\helpers;


use rock\helpers\Json;
use rock\helpers\Sanitize;
use rock\helpers\String;

/**
 * @group base
 * @group helpers
 */
class SanitizeTest extends \PHPUnit_Framework_TestCase {


    public function testDefault()
    {
        $input['foo'] = '<b>foo</b>    ';
        $input['bar'] = ['foo' => ['  <b>foo</b>'], 'bar' => '{"baz" : "<b>bar</b>baz "}'];
        $input['baz'] = '{"foo" : "<b>foo</b>", "bar" : {"foo" : "<b>baz</b>   "}}';
        $input['test'] = serialize(['foo' => ['  <b>foo</b>'], 'bar' => '<b>bar</b>baz ']);
        $result = Sanitize::sanitize($input, [Sanitize::UNSERIALIZE, Sanitize::STRIP_TAGS, 'trim']);
        $this->assertEquals($result['foo'], 'foo');
        $this->assertEquals($result['bar'], ['foo' => ['foo'], 'bar' => ['baz'=>'barbaz']]);
        $this->assertEquals($result['baz'], ['foo' => 'foo', 'bar' => ['foo' => 'baz']]);
        $this->assertEquals($result['test'], ['foo' => ['foo'], 'bar' => 'barbaz']);
    }

    public function testWithFilter()
    {
        $input['foo'] = ' <b>foo</b>     ';
        $input['bar'] = '    <b>bar   </b>';
        $input['baz'] = '{"baz" : " <b> baz  </b>     "}';
        $result = Sanitize::sanitize($input, [
                                                'bar' => [Sanitize::STRIP_TAGS, 'trim'],
                                                'baz' => ['unserialize', Sanitize::STRIP_TAGS, 'trim'],
                                            ]);
        $this->assertEquals($result['foo'], ' <b>foo</b>     ');
        $this->assertEquals($result['bar'], 'bar');
        $this->assertEquals($result['baz'], ['baz' => 'baz']);
    }


    public function testAllTrim()
    {
        $input['foo'] = ' <b>foo</b>     ';
        $input['bar'] = '    <b>bar   </b>';
        $result = Sanitize::sanitize($input, ['trim']);
        $this->assertEquals($result['foo'], '<b>foo</b>');
        $this->assertEquals($result['bar'], '<b>bar   </b>');
    }

    public function testAllUnserialize()
    {
        $input['foo'] = '{"foo" : "foo"}';
        $input['bar'] = '{"bar" : "bar"}';
        $result = Sanitize::sanitize($input, ['unserialize']);
        $this->assertEquals($result['foo'], ['foo' => 'foo']);
        $this->assertEquals($result['bar'], ['bar' => 'bar']);
    }

    public function testUnserialize()
    {
        $input['foo'] = ' <b>foo</b>     ';
        $input['bar'] = '{"bar" : "bar"}';
        $result = Sanitize::sanitize($input, ['bar' => ['unserialize']]);
        $this->assertEquals($result['foo'], ' <b>foo</b>     ');
        $this->assertEquals($result['bar'], ['bar' => 'bar']);
    }

    public function testNumeric()
    {
        $input['foo'] = '-5.7</b>     ';
        $input['bar'] = '5.7';
        $input['baz'] = '{"baz" : "5.7"}';
        $result = Sanitize::sanitize($input, [
                                                'foo' => ['abs', 'ceil'],
                                                'bar' => ['floor'],
                                                'baz' => ['unserialize', 'round'],
                                            ]);
        $this->assertEquals($result['foo'], 6);
        $this->assertEquals($result['bar'], 5);
        $this->assertEquals($result['baz'], ['baz' => 6]);
    }


    public function testBasicTagsTrue()
    {
        $this->assertEquals(Sanitize::sanitize('<b>foo</b>     ', [Sanitize::BASIC_TAGS, 'trim']), '<b>foo</b>');
    }

    public function testBasicTagsFalse()
    {
        $input = '<b>foo</b>     ';
        $filters = [(object)[Sanitize::BASIC_TAGS, ['allowed'=>'i']], 'trim'];
        $this->assertEquals(Sanitize::sanitize($input, $filters), 'foo');
    }

    public function testAny()
    {
        $input = [
            'bar' => '<b>bar</b>     ',
            'foo' => '<script>foo</script>     '
        ];
        $filters = [
            'foo' => [(object)[Sanitize::BASIC_TAGS, ['allowed' => '<script>']]],
            Sanitize::ANY => ['trim']
        ];
        $this->assertEquals(Sanitize::sanitize($input, $filters), ['bar'=>'<b>bar</b>', 'foo'=>'<script>foo</script>']);
    }


    public function testCallback()
    {
        $input = '<b>bar</b>     ';
        $callback = function($value){
            return trim(strip_tags($value));
        };
        $this->assertEquals(Sanitize::sanitize($input, [$callback]), 'bar');
    }

    public function testAddFilter()
    {
        $input = '<b>bar</b>     ';
        $callback = function($value, $params){
            $this->assertEquals($params['test'], 'test');
            return trim(strip_tags($value));
        };

        $this->assertTrue(Sanitize::addFilter('stripTrim', $callback));
        $this->assertEquals(Sanitize::sanitize($input, [(object)['stripTrim', ['test'=>'test']]]), 'bar');
    }


    public function testFunctionParams()
    {
        $input = 'АБВГде';
        $this->assertEquals(Sanitize::sanitize($input, [(object)['mb_strtolower', ['utf-8']]]), 'абвгде');
    }
}
 