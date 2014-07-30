<?php
namespace rockunit\core\validation;

use \DateTime;
use rock\validation\rules\Between;

class BetweenTest extends \PHPUnit_Framework_TestCase
{
    public function providerValid()
    {
        return array(
            array(10, 20, false, ''),
            array(10, 20, true, ''),
            array(0, 1, true, 0),
            array(0, 1, true, 1),
            array(10, 20, false, 15),
            array(10, 20, true, 20),
            array(-10, 20, false, -5),
            array(-10, 20, false, 0),
            array('a', 'z', false, 'j'),
            array(
                new DateTime('yesterday'),
                new DateTime('tomorrow'),
                false,
                new DateTime('now')
            ),
        );
    }

    public function providerInvalid()
    {
        return array(
            array(0, 1, false, 0),
            array(0, 1, false, 1),
            array(0, 1, false, 2),
            array(0, 1, false, -1),
            array(10, 20, false, 999),
            array(10, 20, false, 20),
            array(-10, 20, false, -11),
            array('a', 'j', false, 'z'),
            array(
                new DateTime('yesterday'),
                new DateTime('now'),
                false,
                new DateTime('tomorrow')
            ),
        );
    }

    /**
     * @dataProvider providerValid
     */
    public function testValuesBetweenBoundsShouldPass($min, $max, $inclusive, $input)
    {
        $o = new Between($min, $max, $inclusive);
        $this->assertTrue($o->__invoke($input));
        $this->assertTrue($o->assert($input));
        $this->assertTrue($o->check($input));
    }

    /**
     * @dataProvider providerInvalid
     * @expectedException \rock\validation\exceptions\BetweenException
     */
    public function testValuesOutBoundsShouldRaiseException($min, $max, $inclusive, $input)
    {
        $o = new Between($min, $max, $inclusive);
        $this->assertFalse($o->__invoke($input));
        $this->assertFalse($o->assert($input));
    }

    /**
     * @expectedException \rock\validation\exceptions\ComponentException
     */
    public function testInvalidConstructionParamsShouldRaiseException()
    {
        $o = new Between(10, 5);
    }
}

