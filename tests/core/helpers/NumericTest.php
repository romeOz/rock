<?php

namespace rockunit\core\helpers;


use rock\helpers\Numeric;

class NumericTest extends \PHPUnit_Framework_TestCase
{
    public function testParity()
    {
        $this->assertTrue(Numeric::isParity(2));
        $this->assertFalse(Numeric::isParity(3));
    }

    public function testToNumeric()
    {
        $this->assertSame(Numeric::toNumeric('3.14'), 3.14);
        $this->assertSame(Numeric::toNumeric('7'), 7);
        $this->assertSame(Numeric::toNumeric('foo'), 0);
    }
}
 