<?php

namespace rockunit\core\helpers;


use rock\helpers\StringHelper;

/**
 * @group base
 * @group helpers
 */
class StringHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider providerValue
     */
    public function testReplace($value, array $dataReplace, $removeBraces = true, $result)
    {
        $this->assertSame($result, StringHelper::replace($value, $dataReplace, $removeBraces));
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
        $this->assertSame('foo', StringHelper::lower('Foo'));
        $this->assertSame('абв', StringHelper::lower('АбВ'));

        // empty
        $this->assertSame('', StringHelper::lower(''));
    }

    public function testUpper()
    {
        $this->assertSame('FOO', StringHelper::upper('Foo'));
        $this->assertSame('АБВ', StringHelper::upper('аБв'));

        // empty
        $this->assertSame('', StringHelper::upper(''));
    }

    public function testUpperFirst()
    {
        $this->assertSame('Foo', StringHelper::upperFirst('foo'));
        $this->assertSame('Абв', StringHelper::upperFirst('абв'));

        // empty
        $this->assertSame('', StringHelper::upperFirst(''));
    }

    public function testLowerFirst()
    {
        $this->assertSame('foO', StringHelper::lowerFirst('FoO'));
        $this->assertSame('абВ', StringHelper::lowerFirst('АбВ'));

        // empty
        $this->assertSame('', StringHelper::lowerFirst(''));
    }

    public function testTruncateWords()
    {
        $this->assertSame('Hello', StringHelper::truncateWords('Hello', 7));
        $this->assertSame('', StringHelper::truncateWords('Hello', 3));
        $this->assertSame('Hello...', StringHelper::truncateWords('Hello world', 7));
    }

    public function testTruncate()
    {
        $this->assertSame('Hello', StringHelper::truncate('Hello', 7));
        $this->assertSame('Hell...', StringHelper::truncate('Hello', 4));
    }

    public function testStristr()
    {
        $this->assertSame('fOo', StringHelper::stritr('fOo', []));
        $this->assertSame('fRR', StringHelper::stritr('fOo', ['t' => 'k', 'o' => 'R']));
        $this->assertSame('аЁЁ', StringHelper::stritr('аЁЁ', ['в' => 'п', 'б' => 'Ё']));

        $this->assertSame('fRR', StringHelper::stritr('fOo', 'o', 'R'));
        $this->assertSame('аЁЁ', StringHelper::stritr('аБб', 'б', 'Ё'));
    }

    public function testLtrimWords()
    {
        $words = ['foo', 'bar'];
        $this->assertSame('text', StringHelper::ltrimWords('foo text', $words));
        $this->assertSame('hello world', StringHelper::ltrimWords('hello world', $words));
        $this->assertSame('hello world! foo', StringHelper::ltrimWords('hello world! foo', $words));
    }

    public function testRtrimWords()
    {
        $words = ['foo', 'bar'];
        $this->assertSame('text', StringHelper::rtrimWords('text bar', $words));
        $this->assertSame('hello world', StringHelper::rtrimWords('hello world', $words));
        $this->assertSame('foo hello world!', StringHelper::rtrimWords('foo hello world!', $words));
    }

    public function testReplaceRandChars()
    {
        $this->assertContains('*', StringHelper::replaceRandChars('Hello world!'));
    }

    public function testEncode()
    {
        $this->assertSame(htmlspecialchars('<b>foo</b> bar'), StringHelper::encode('<b>foo</b> bar'));
        $this->assertSame("a&lt;&gt;&amp;&quot;&#039;�", StringHelper::encode("a<>&\"'\x80"));
        $this->assertSame("Tom &amp; Jerry", StringHelper::encode("Tom & Jerry"));
    }

    public function testDecode()
    {
        $this->assertSame('<b>foo</b> bar', StringHelper::decode(htmlspecialchars('<b>foo</b> bar')));
        $this->assertSame("a<>&\"'", StringHelper::decode("a&lt;&gt;&amp;&quot;&#039;"));
    }

    public function testStrlen()
    {
        $this->assertEquals(4, StringHelper::byteLength('this'));
        $this->assertEquals(6, StringHelper::byteLength('это'));
    }

    public function testSubstr()
    {
        $this->assertEquals('th', StringHelper::byteSubstr('this', 0, 2));
        $this->assertEquals('э', StringHelper::byteSubstr('это', 0, 2));
        $this->assertEquals('abcdef', StringHelper::byteSubstr('abcdef', 0));
        $this->assertEquals('abcdef', StringHelper::byteSubstr('abcdef', 0, null));
        $this->assertEquals('de', StringHelper::byteSubstr('abcdef', 3, 2));
        $this->assertEquals('def', StringHelper::byteSubstr('abcdef', 3));
        $this->assertEquals('def', StringHelper::byteSubstr('abcdef', 3, null));
        $this->assertEquals('cd', StringHelper::byteSubstr('abcdef', -4, 2));
        $this->assertEquals('cdef', StringHelper::byteSubstr('abcdef', -4));
        $this->assertEquals('cdef', StringHelper::byteSubstr('abcdef', -4, null));
        $this->assertEquals('', StringHelper::byteSubstr('abcdef', 4, 0));
        $this->assertEquals('', StringHelper::byteSubstr('abcdef', -4, 0));
        $this->assertEquals('это', StringHelper::byteSubstr('это', 0));
        $this->assertEquals('это', StringHelper::byteSubstr('это', 0, null));
        $this->assertEquals('т', StringHelper::byteSubstr('это', 2, 2));
        $this->assertEquals('то', StringHelper::byteSubstr('это', 2));
        $this->assertEquals('то', StringHelper::byteSubstr('это', 2, null));
        $this->assertEquals('т', StringHelper::byteSubstr('это', -4, 2));
        $this->assertEquals('то', StringHelper::byteSubstr('это', -4));
        $this->assertEquals('то', StringHelper::byteSubstr('это', -4, null));
        $this->assertEquals('', StringHelper::byteSubstr('это', 4, 0));
        $this->assertEquals('', StringHelper::byteSubstr('это', -4, 0));
    }

    public function testQuotes()
    {
        $this->assertSame('', StringHelper::quotes(''));
        $this->assertSame("'foo'", StringHelper::quotes('foo'));
    }

    public function testDoubleQuotes()
    {
        $this->assertSame('', StringHelper::doubleQuotes(''));
        $this->assertSame('"foo"', StringHelper::doubleQuotes('foo'));
    }

    public function testRemoveSpaces()
    {
        $this->assertSame('fooBar', StringHelper::removeSpaces(' foo Bar     '));
    }

    /**
     * @dataProvider providerContainsValid
     */
    public function testContainsValid($contains, $input, $identical=false)
    {
        $this->assertTrue(StringHelper::contains($input, $contains, $identical));
    }

    /**
     * @dataProvider providerContainsInvalid
     */
    public function testContainsInvalid($contains, $input, $identical=false)
    {
        $this->assertFalse(StringHelper::contains($input, $contains, $identical));
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
        $this->assertSame('hello world', StringHelper::lconcat($input, 'hello '));
        $input = null;
        $this->assertSame(null, StringHelper::lconcat($input, 'hello '));
        $input = '';
        $this->assertSame(null, StringHelper::lconcat($input, 'hello '));
        $input = '';
        $this->assertSame('', StringHelper::lconcat($input, 'hello ', ''));
    }

    public function testRconcat()
    {
        $input = 'hello';
        $this->assertSame('hello world', StringHelper::rconcat($input, ' world'));
        $input = null;
        $this->assertSame(null, StringHelper::lconcat($input, ' world'));
        $input = '';
        $this->assertSame(null, StringHelper::lconcat($input, ' world'));
        $input = '';
        $this->assertSame('', StringHelper::lconcat($input, ' world', ''));
    }

    public function testIsRegexp()
    {
        $input = '~/\\w+/';
        $this->assertTrue(StringHelper::isRegexp($input));
        $this->assertSame('/\\w+/', $input);

        $input = '/\\w+/';
        $this->assertFalse(StringHelper::isRegexp($input));
        $this->assertSame('/\\w+/', $input);
    }

    public function testBasename()
    {
        $this->assertEquals('', StringHelper::basename(''));
        $this->assertEquals('file', StringHelper::basename('file'));
        $this->assertEquals('file.test', StringHelper::basename('file.test', '.test2'));
        $this->assertEquals('file', StringHelper::basename('file.test', '.test'));
        $this->assertEquals('file', StringHelper::basename('/file'));
        $this->assertEquals('file.test', StringHelper::basename('/file.test', '.test2'));
        $this->assertEquals('file', StringHelper::basename('/file.test', '.test'));
        $this->assertEquals('file', StringHelper::basename('/path/to/file'));
        $this->assertEquals('file.test', StringHelper::basename('/path/to/file.test', '.test2'));
        $this->assertEquals('file', StringHelper::basename('/path/to/file.test', '.test'));
        $this->assertEquals('file', StringHelper::basename('\file'));
        $this->assertEquals('file.test', StringHelper::basename('\file.test', '.test2'));
        $this->assertEquals('file', StringHelper::basename('\file.test', '.test'));
        $this->assertEquals('file', StringHelper::basename('C:\file'));
        $this->assertEquals('file.test', StringHelper::basename('C:\file.test', '.test2'));
        $this->assertEquals('file', StringHelper::basename('C:\file.test', '.test'));
        $this->assertEquals('file', StringHelper::basename('C:\path\to\file'));
        $this->assertEquals('file.test', StringHelper::basename('C:\path\to\file.test', '.test2'));
        $this->assertEquals('file', StringHelper::basename('C:\path\to\file.test', '.test'));
        // mixed paths
        $this->assertEquals('file.test', StringHelper::basename('/path\to/file.test'));
        $this->assertEquals('file.test', StringHelper::basename('/path/to\file.test'));
        $this->assertEquals('file.test', StringHelper::basename('\path/to\file.test'));
        // \ and / in suffix
        $this->assertEquals('file', StringHelper::basename('/path/to/filete/st', 'te/st'));
        $this->assertEquals('st', StringHelper::basename('/path/to/filete/st', 'te\st'));
        $this->assertEquals('file', StringHelper::basename('/path/to/filete\st', 'te\st'));
        $this->assertEquals('st', StringHelper::basename('/path/to/filete\st', 'te/st'));
        // http://www.php.net/manual/en/function.basename.php#72254
        $this->assertEquals('foo', StringHelper::basename('/bar/foo/'));
        $this->assertEquals('foo', StringHelper::basename('\\bar\\foo\\'));
    }


    /**
     * @dataProvider providerStartsWith
     */
    public function testStartsWith($result, $string, $with)
    {
        // case sensitive version check
        $this->assertSame($result, StringHelper::startsWith($string, $with));
        // case insensitive version check
        $this->assertSame($result, StringHelper::startsWith($string, $with, false));
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
        $this->assertFalse(StringHelper::startsWith('Abc', 'a'));
        $this->assertFalse(StringHelper::startsWith('üЯ multibyte', 'Üя multibyte'));
    }

    public function testStartsWithCaseInsensitive()
    {
        $this->assertTrue(StringHelper::startsWith('sTrInG', 'StRiNg', false));
        $this->assertTrue(StringHelper::startsWith('CaSe', 'cAs', false));
        $this->assertTrue(StringHelper::startsWith('HTTP://BÜrger.DE/', 'http://bürger.de', false));
        $this->assertTrue(StringHelper::startsWith('üЯйΨB', 'ÜяЙΨ', false));
    }

    /**
     * @dataProvider providerEndsWith
     */
    public function testEndsWith($result, $string, $with)
    {
        // case sensitive version check
        $this->assertSame($result, StringHelper::endsWith($string, $with));
        // case insensitive version check
        $this->assertSame($result, StringHelper::endsWith($string, $with, false));
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
        $this->assertFalse(StringHelper::endsWith('string', 'G'));
        $this->assertFalse(StringHelper::endsWith('multibyte строка', 'А'));
    }

    public function testEndsWithCaseInsensitive()
    {
        $this->assertTrue(StringHelper::endsWith('sTrInG', 'StRiNg', false));
        $this->assertTrue(StringHelper::endsWith('string', 'nG', false));
        $this->assertTrue(StringHelper::endsWith('BüЯйΨ', 'ÜяЙΨ', false));
    }
}