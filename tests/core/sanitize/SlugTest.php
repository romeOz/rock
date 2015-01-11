<?php

namespace rockunit\core\sanitize;


use rock\sanitize\Sanitize;

class SlugTest extends \PHPUnit_Framework_TestCase
{
    public function testSkip()
    {
        $s = Sanitize::slug();
        $this->assertSame(7, $s->sanitize(7));
    }

    public function testTranslit()
    {
        $s = Sanitize::slug();
        $this->assertSame('foo', $s->sanitize('Foo'));
        $s = Sanitize::slug('-', false);
        $this->assertSame('AbV', $s->sanitize('АбВ'));
    }
} 