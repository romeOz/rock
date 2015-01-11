<?php

namespace rockunit\core\sanitize;


use rock\sanitize\Sanitize;

class SlugTest extends \PHPUnit_Framework_TestCase
{
    public function testSkip()
    {
        $s = Sanitize::translit();
        $this->assertSame(7, $s->sanitize(7));
    }

    public function testTranslit()
    {
        $s = Sanitize::translit();
        $this->assertSame('foo', $s->sanitize('Foo'));
        $s = Sanitize::translit('-', false);
        $this->assertSame('AbV', $s->sanitize('АбВ'));
    }
} 