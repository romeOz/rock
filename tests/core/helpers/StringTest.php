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
    public function testReplace($value, array $dataReplace, $removeBraces = true, $result)
    {
        $this->assertSame($result, String::replace($value, $dataReplace, $removeBraces));
    }

    public function providerValue()
    {
        return [
            [['foo'], [],true, ['foo']],
            ['', [],true, ''],
            ['hello {value} !!!', ['value'=> 'world'], true, 'hello world !!!'],
            ['hello {{name}} !!!', ['name'=> 'world'], true,'hello world !!!'],
            ['hello {{unknown}} !!!', ['name'=> 'world'], true,'hello  !!!'],
            ['hello {value} !!!', [], true, 'hello  !!!'],
            ['hello {{unknown}} !!!', ['name'=> 'world'], false,'hello {{unknown}} !!!'],
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
 