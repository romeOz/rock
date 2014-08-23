<?php

namespace rockunit\core\helpers;


use rock\helpers\Html;

/**
 * @group base
 * @group helpers
 */
class HtmlTest extends \PHPUnit_Framework_TestCase
{
    public function testEncode()
    {
        $this->assertEquals("a&lt;&gt;&amp;&quot;&#039;�", Html::encode("a<>&\"'\x80"));
    }

    public function testDecode()
    {
        $this->assertEquals("a<>&\"'", Html::decode("a&lt;&gt;&amp;&quot;&#039;"));
    }

    public function testTag()
    {
        $this->assertEquals('<br>', Html::tag('br'));
        $this->assertEquals('<span></span>', Html::tag('span'));
        $this->assertEquals('<div>content</div>', Html::tag('div', 'content'));
        $this->assertEquals('<input type="text" name="test" value="&lt;&gt;">', Html::tag('input', '', ['type' => 'text', 'name' => 'test', 'value' => '<>']));
        $this->assertEquals('<span disabled></span>', Html::tag('span', '', ['disabled' => true]));
        $this->assertEquals('<span data-foo="test"></span>', Html::tag('span', '', ['data' => ['foo' => 'test']]));
    }

    public function testStyle()
    {
        $content = 'a <>';
        $this->assertEquals("<style>{$content}</style>", Html::style($content));
        $this->assertEquals("<style type=\"text/less\">{$content}</style>", Html::style($content, ['type' => 'text/less']));
    }

    public function testScript()
    {
        $content = 'a <>';
        $this->assertEquals("<script>{$content}</script>", Html::script($content));
        $this->assertEquals("<script type=\"text/js\">{$content}</script>", Html::script($content, ['type' => 'text/js']));
    }

    public function testCssFile()
    {
        $this->assertEquals('<link href="http://example.com/" rel="stylesheet">', Html::cssFile('http://example.com'));
        $this->assertEquals('<link href="http://site.com/" rel="stylesheet">', Html::cssFile(''));
        $this->assertEquals("<!--[if IE 9]>\n" . '<link href="http://example.com/" rel="stylesheet">' . "\n<![endif]-->", Html::cssFile('http://example.com', ['condition' => 'IE 9']));
    }

    public function testJsFile()
    {
        $this->assertEquals('<script src="http://example.com/"></script>', Html::jsFile('http://example.com'));
        $this->assertEquals('<script src="http://site.com/"></script>', Html::jsFile(''));
        $this->assertEquals("<!--[if IE 9]>\n" . '<script src="http://example.com/"></script>' . "\n<![endif]-->", Html::jsFile('http://example.com', ['condition' => 'IE 9']));
    }

    public function testRenderAttributes()
    {
        $this->assertEquals('', Html::renderTagAttributes([]));
        $this->assertEquals(' name="test" value="1&lt;&gt;"', Html::renderTagAttributes(['name' => 'test', 'empty' => null, 'value' => '1<>']));
        $this->assertEquals(' checked disabled', Html::renderTagAttributes(['checked' => true, 'disabled' => true, 'hidden' => false]));
    }
}
 