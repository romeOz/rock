<?php

namespace rockunit\core\helpers;


use rock\helpers\String;

/**
 * @group base
 * @group helpers
 */
class StringTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider providerValue
     */
    public function testReplace($value, array $dataReplace, $result)
    {
        $this->assertSame(String::replace($value, $dataReplace), $result);
    }

    public function providerValue()
    {
        return [
            [['foo'], [], ['foo']],
            ['', [], ''],
            ['hello {value} !!!', ['value'=> 'world'], 'hello world !!!'],
            ['hello {{name}} !!!', ['name'=> 'world'], 'hello world !!!'],
            ['hello {{unknown}} !!!', ['name'=> 'world'], 'hello {{unknown}} !!!'],
            ['hello {value} !!!', [], 'hello {value} !!!'],
        ];
    }

    public function testLower()
    {
        $this->assertSame(String::lower('Foo'), 'foo');

        // empty
        $this->assertSame(String::lower(''), '');
    }

    public function testUpper()
    {
        $this->assertSame(String::upper('Foo'), 'FOO');

        // empty
        $this->assertSame(String::upper(''), '');
    }

    public function testUpperFirst()
    {
        $this->assertSame(String::upperFirst('foo'), 'Foo');

        // empty
        $this->assertSame(String::upperFirst(''), '');
    }

    public function testTruncateWords()
    {
        $this->assertSame(String::truncateWords('Hello', 7), 'Hello');
        $this->assertSame(String::truncateWords('Hello', 3), '');
        $this->assertSame(String::truncateWords('Hello world', 7), 'Hello...');
    }

    public function testTruncate()
    {
        $this->assertSame(String::truncate('Hello', 7), 'Hello');
        $this->assertSame(String::truncate('Hello', 4), 'Hell...');
    }
}
 