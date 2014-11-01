<?php

namespace rockunit\core\sanitize;


use rock\sanitize\Sanitize;

class AbsTest extends \PHPUnit_Framework_TestCase
{
    public function testAbs()
    {
        $s = Sanitize::abs();
        $this->assertSame(7.7, $s->sanitize('-7.7'));
    }
} 