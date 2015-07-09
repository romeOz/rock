<?php

namespace rockunit;


use rock\base\Alias;
use rock\Rock;

/**
 * @group template
 */
class TemplateTest extends \PHPUnit_Framework_TestCase
{
    public function testLink()
    {
        $template = Rock::$app->template;
        Alias::setAlias('test_link', 'http://test/foo/');
        $this->assertSame('/foo', $template->replace("[[~test_link]]"));
    }
}