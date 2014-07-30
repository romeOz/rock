<?php
namespace rockunit\core\validation;

use rock\validation\rules\Negative;

class NegativeTest extends \PHPUnit_Framework_TestCase
{
    /** @var Negative */
    protected $negativeValidator;

    protected function setUp()
    {
        $this->negativeValidator = new Negative;
    }

    /**
     * @dataProvider providerForNegative
     *
     */
    public function testNegativeShouldPass($input)
    {
        $this->assertTrue($this->negativeValidator->assert($input));
        $this->assertTrue($this->negativeValidator->__invoke($input));
        $this->assertTrue($this->negativeValidator->check($input));
    }

    /**
     * @dataProvider providerForNotNegative
     * @expectedException \rock\validation\exceptions\NegativeException
     */
    public function testNotNegativeNumbersShouldThrowNegativeException($input)
    {
        $this->assertFalse($this->negativeValidator->__invoke($input));
        $this->assertFalse($this->negativeValidator->assert($input));
    }

    public function providerForNegative()
    {
        return array(
            array(''),
            array('-1.44'),
            array(-1e-5),
            array(-10),
        );
    }

    public function providerForNotNegative()
    {
        return array(
            array(0),
            array(-0),
            array(null),
            array('a'),
            array(' '),
            array('Foo'),
            array(16),
            array('165'),
            array(123456),
            array(1e10),
        );
    }
}

