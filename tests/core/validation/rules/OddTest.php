<?php
namespace rockunit\core\validation;

use rock\validation\rules\Odd;

class OddTest extends \PHPUnit_Framework_TestCase
{
    /** @var Odd  */
    protected $object;

    protected function setUp()
    {
        $this->object = new Odd;
    }

    /**
     * @dataProvider providerForOdd
     *
     */
    public function testOdd($input)
    {
        $this->assertTrue($this->object->assert($input));
        $this->assertTrue($this->object->__invoke($input));
        $this->assertTrue($this->object->check($input));
    }

    /**
     * @dataProvider providerForNotOdd
     * @expectedException \rock\validation\exceptions\OddException
     */
    public function testNotOdd($input)
    {
        $this->assertFalse($this->object->__invoke($input));
        $this->assertFalse($this->object->assert($input));
    }

    public function providerForOdd()
    {
        return array(
            array(''),
            array(-5),
            array(-1),
            array(1),
            array(13),
        );
    }

    public function providerForNotOdd()
    {
        return array(
            array(-2),
            array(-0),
            array(0),
            array(32),
        );
    }
}

