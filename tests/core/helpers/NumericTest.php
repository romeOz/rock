<?php

namespace rockunit\core\helpers;


use rock\helpers\NumericHelper;

class NumericTest extends \PHPUnit_Framework_TestCase
{
    public function testParity()
    {
        $this->assertTrue(NumericHelper::isParity(2));
        $this->assertFalse(NumericHelper::isParity(3));
    }

    public function testToNumeric()
    {
        $this->assertSame(NumericHelper::toNumeric('3.14'), 3.14);
        $this->assertSame(NumericHelper::toNumeric('7'), 7);
        $this->assertSame(NumericHelper::toNumeric('foo'), 0);
    }
}
 