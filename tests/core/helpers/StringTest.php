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
        $this->assertSame('foo', String::lower('Foo'));
        $this->assertSame('абв', String::lower('АбВ'));

        // empty
        $this->assertSame('', String::lower(''));
    }

    public function testUpper()
    {
        $this->assertSame('FOO', String::upper('Foo'));
        $this->assertSame('АБВ', String::upper('аБв'));

        // empty
        $this->assertSame('', String::upper(''));
    }

    public function testUpperFirst()
    {
        $this->assertSame('Foo', String::upperFirst('foo'));
        $this->assertSame('Абв', String::upperFirst('абв'));

        // empty
        $this->assertSame('', String::upperFirst(''));
    }

    public function testLowerFirst()
    {
        $this->assertSame('foO', String::lowerFirst('FoO'));
        $this->assertSame('абВ', String::lowerFirst('АбВ'));

        // empty
        $this->assertSame('', String::lowerFirst(''));
    }

    public function testTruncateWords()
    {
        $this->assertSame('Hello', String::truncateWords('Hello', 7));
        $this->assertSame('', String::truncateWords('Hello', 3));
        $this->assertSame('Hello...', String::truncateWords('Hello world', 7));
    }

    public function testTruncate()
    {
        $this->assertSame('Hello', String::truncate('Hello', 7));
        $this->assertSame('Hell...', String::truncate('Hello', 4));
    }

    public function testTranslit()
    {
        $this->assertSame('foo', String::translit('foo'));
        $this->assertSame('AbV', String::translit('АбВ'));
    }

    public function testStristr()
    {
        $this->assertSame('fOo', String::stritr('fOo', []));
        $this->assertSame('fRR', String::stritr('fOo', ['t' => 'k', 'o' => 'R']));
        $this->assertSame('аЁЁ', String::stritr('аЁЁ', ['в' => 'п', 'б' => 'Ё']));

        $this->assertSame('fRR', String::stritr('fOo', 'o', 'R'));
        $this->assertSame('аЁЁ', String::stritr('аБб', 'б', 'Ё'));
    }

    public function testLtrimWords()
    {
        $words = ['foo', 'bar'];
        $this->assertSame('text', String::ltrimWords('foo text', $words));
        $this->assertSame('hello world', String::ltrimWords('hello world', $words));
        $this->assertSame('hello world! foo', String::ltrimWords('hello world! foo', $words));
    }

    public function testRtrimWords()
    {
        $words = ['foo', 'bar'];
        $this->assertSame('text', String::rtrimWords('text bar', $words));
        $this->assertSame('hello world', String::rtrimWords('hello world', $words));
        $this->assertSame('foo hello world!', String::rtrimWords('foo hello world!', $words));
    }

    public function testReplaceRandChars()
    {
        $this->assertContains('*', String::replaceRandChars('Hello world!'));
    }

    public function testEncode()
    {
        $this->assertSame(htmlspecialchars('<b>foo</b> bar'), String::encode('<b>foo</b> bar'));
        $this->assertSame("a&lt;&gt;&amp;&quot;&#039;�", String::encode("a<>&\"'\x80"));
        $this->assertSame("Tom &amp; Jerry", String::encode("Tom & Jerry"));
    }

    public function testDecode()
    {
        $this->assertSame('<b>foo</b> bar', String::decode(htmlspecialchars('<b>foo</b> bar')));
        $this->assertSame("a<>&\"'", String::decode("a&lt;&gt;&amp;&quot;&#039;"));
    }

    public function testStrlen()
    {
        $this->assertEquals(4, String::byteLength('this'));
        $this->assertEquals(6, String::byteLength('это'));
    }

    public function testSubstr()
    {
        $this->assertEquals('th', String::byteSubstr('this', 0, 2));
        $this->assertEquals('э', String::byteSubstr('это', 0, 2));
        $this->assertEquals('abcdef', String::byteSubstr('abcdef', 0));
        $this->assertEquals('abcdef', String::byteSubstr('abcdef', 0, null));
        $this->assertEquals('de', String::byteSubstr('abcdef', 3, 2));
        $this->assertEquals('def', String::byteSubstr('abcdef', 3));
        $this->assertEquals('def', String::byteSubstr('abcdef', 3, null));
        $this->assertEquals('cd', String::byteSubstr('abcdef', -4, 2));
        $this->assertEquals('cdef', String::byteSubstr('abcdef', -4));
        $this->assertEquals('cdef', String::byteSubstr('abcdef', -4, null));
        $this->assertEquals('', String::byteSubstr('abcdef', 4, 0));
        $this->assertEquals('', String::byteSubstr('abcdef', -4, 0));
        $this->assertEquals('это', String::byteSubstr('это', 0));
        $this->assertEquals('это', String::byteSubstr('это', 0, null));
        $this->assertEquals('т', String::byteSubstr('это', 2, 2));
        $this->assertEquals('то', String::byteSubstr('это', 2));
        $this->assertEquals('то', String::byteSubstr('это', 2, null));
        $this->assertEquals('т', String::byteSubstr('это', -4, 2));
        $this->assertEquals('то', String::byteSubstr('это', -4));
        $this->assertEquals('то', String::byteSubstr('это', -4, null));
        $this->assertEquals('', String::byteSubstr('это', 4, 0));
        $this->assertEquals('', String::byteSubstr('это', -4, 0));
    }

    public function testQuotes()
    {
        $this->assertSame('', String::quotes(''));
        $this->assertSame("'foo'", String::quotes('foo'));
    }

    public function testDoubleQuotes()
    {
        $this->assertSame('', String::doubleQuotes(''));
        $this->assertSame('"foo"', String::doubleQuotes('foo'));
    }

    public function testRemoveSpaces()
    {
        $this->assertSame('fooBar', String::removeSpaces(' foo Bar     '));
    }

    /**
     * @dataProvider providerContainsValid
     */
    public function testContainsValid($contains, $input, $identical=false)
    {
        $this->assertTrue(String::contains($input, $contains, $identical));
    }

    /**
     * @dataProvider providerContainsInvalid
     */
    public function testContainsInvalid($contains, $input, $identical=false)
    {
        $this->assertFalse(String::contains($input, $contains, $identical));
    }

    public function providerContainsValid()
    {
        return [
            ['foo', 'barbazFOO'],
            ['foo', 'barbazfoo'],
            ['foo', 'foobazfoo'],
        ];
    }

    public function providerContainsInvalid()
    {
        return [
            ['foo', 'barfaabaz'],
            ['foo', 'barbazFOO', true],
            ['foo', 'faabarbaz'],
        ];
    }

    public function testLconcat()
    {
        $input = 'world';
        $this->assertSame('hello world', String::lconcat($input, 'hello '));
        $input = null;
        $this->assertSame(null, String::lconcat($input, 'hello '));
        $input = '';
        $this->assertSame(null, String::lconcat($input, 'hello '));
        $input = '';
        $this->assertSame('', String::lconcat($input, 'hello ', ''));
    }

    public function testRconcat()
    {
        $input = 'hello';
        $this->assertSame('hello world', String::rconcat($input, ' world'));
        $input = null;
        $this->assertSame(null, String::lconcat($input, ' world'));
        $input = '';
        $this->assertSame(null, String::lconcat($input, ' world'));
        $input = '';
        $this->assertSame('', String::lconcat($input, ' world', ''));
    }

    public function testIsRegexp()
    {
        $input = '~/\\w+/';
        $this->assertTrue(String::isRegexp($input));
        $this->assertSame('/\\w+/', $input);

        $input = '/\\w+/';
        $this->assertFalse(String::isRegexp($input));
        $this->assertSame('/\\w+/', $input);
    }

    public function testBasename()
    {
        $this->assertEquals('', String::basename(''));
        $this->assertEquals('file', String::basename('file'));
        $this->assertEquals('file.test', String::basename('file.test', '.test2'));
        $this->assertEquals('file', String::basename('file.test', '.test'));
        $this->assertEquals('file', String::basename('/file'));
        $this->assertEquals('file.test', String::basename('/file.test', '.test2'));
        $this->assertEquals('file', String::basename('/file.test', '.test'));
        $this->assertEquals('file', String::basename('/path/to/file'));
        $this->assertEquals('file.test', String::basename('/path/to/file.test', '.test2'));
        $this->assertEquals('file', String::basename('/path/to/file.test', '.test'));
        $this->assertEquals('file', String::basename('\file'));
        $this->assertEquals('file.test', String::basename('\file.test', '.test2'));
        $this->assertEquals('file', String::basename('\file.test', '.test'));
        $this->assertEquals('file', String::basename('C:\file'));
        $this->assertEquals('file.test', String::basename('C:\file.test', '.test2'));
        $this->assertEquals('file', String::basename('C:\file.test', '.test'));
        $this->assertEquals('file', String::basename('C:\path\to\file'));
        $this->assertEquals('file.test', String::basename('C:\path\to\file.test', '.test2'));
        $this->assertEquals('file', String::basename('C:\path\to\file.test', '.test'));
        // mixed paths
        $this->assertEquals('file.test', String::basename('/path\to/file.test'));
        $this->assertEquals('file.test', String::basename('/path/to\file.test'));
        $this->assertEquals('file.test', String::basename('\path/to\file.test'));
        // \ and / in suffix
        $this->assertEquals('file', String::basename('/path/to/filete/st', 'te/st'));
        $this->assertEquals('st', String::basename('/path/to/filete/st', 'te\st'));
        $this->assertEquals('file', String::basename('/path/to/filete\st', 'te\st'));
        $this->assertEquals('st', String::basename('/path/to/filete\st', 'te/st'));
        // http://www.php.net/manual/en/function.basename.php#72254
        $this->assertEquals('foo', String::basename('/bar/foo/'));
        $this->assertEquals('foo', String::basename('\\bar\\foo\\'));
    }


    /**
     * @dataProvider providerStartsWith
     */
    public function testStartsWith($result, $string, $with)
    {
        // case sensitive version check
        $this->assertSame($result, String::startsWith($string, $with));
        // case insensitive version check
        $this->assertSame($result, String::startsWith($string, $with, false));
    }

    /**
     * Rules that should work the same for case-sensitive and case-insensitive `startsWith()`
     */
    public function providerStartsWith()
    {
        return [
            // positive check
            [true, '', ''],
            [true, '', null],
            [true, 'string', ''],
            [true, ' string', ' '],
            [true, 'abc', 'abc'],
            [true, 'Bürger', 'Bürger'],
            [true, '我Я multibyte', '我Я'],
            [true, 'Qנטשופ צרכנות', 'Qנ'],
            [true, 'ไทย.idn.icann.org', 'ไ'],
            [true, '!?+', "\x21\x3F"],
            [true, "\x21?+", '!?'],
            // false-positive check
            [false, '', ' '],
            [false, ' ', '  '],
            [false, 'Abc', 'Abcde'],
            [false, 'abc', 'abe'],
            [false, 'abc', 'b'],
            [false, 'abc', 'c'],
        ];
    }

    public function testStartsWithCaseSensitive()
    {
        $this->assertFalse(String::startsWith('Abc', 'a'));
        $this->assertFalse(String::startsWith('üЯ multibyte', 'Üя multibyte'));
    }

    public function testStartsWithCaseInsensitive()
    {
        $this->assertTrue(String::startsWith('sTrInG', 'StRiNg', false));
        $this->assertTrue(String::startsWith('CaSe', 'cAs', false));
        $this->assertTrue(String::startsWith('HTTP://BÜrger.DE/', 'http://bürger.de', false));
        $this->assertTrue(String::startsWith('üЯйΨB', 'ÜяЙΨ', false));
    }

    /**
     * @dataProvider providerEndsWith
     */
    public function testEndsWith($result, $string, $with)
    {
        // case sensitive version check
        $this->assertSame($result, String::endsWith($string, $with));
        // case insensitive version check
        $this->assertSame($result, String::endsWith($string, $with, false));
    }

    /**
     * Rules that should work the same for case-sensitive and case-insensitive `endsWith()`
     */
    public function providerEndsWith()
    {
        return [
            // positive check
            [true, '', ''],
            [true, '', null],
            [true, 'string', ''],
            [true, 'string ', ' '],
            [true, 'string', 'g'],
            [true, 'abc', 'abc'],
            [true, 'Bürger', 'Bürger'],
            [true, 'Я multibyte строка我!', ' строка我!'],
            [true, '+!?', "\x21\x3F"],
            [true, "+\x21?", "!\x3F"],
            [true, 'נטשופ צרכנות', 'ת'],
            // false-positive check
            [false, '', ' '],
            [false, ' ', '  '],
            [false, 'aaa', 'aaaa'],
            [false, 'abc', 'abe'],
            [false, 'abc', 'a'],
            [false, 'abc', 'b'],
        ];
    }

    public function testEndsWithCaseSensitive()
    {
        $this->assertFalse(String::endsWith('string', 'G'));
        $this->assertFalse(String::endsWith('multibyte строка', 'А'));
    }

    public function testEndsWithCaseInsensitive()
    {
        $this->assertTrue(String::endsWith('sTrInG', 'StRiNg', false));
        $this->assertTrue(String::endsWith('string', 'nG', false));
        $this->assertTrue(String::endsWith('BüЯйΨ', 'ÜяЙΨ', false));
    }
}