<?php
namespace rockunit\core\validation;

use rock\validation\rules\Even;

class EvenTest extends \PHPUnit_Framework_TestCase
{
    /** @var Even  */
    protected $evenValidator;

    protected function setUp()
    {
        $this->evenValidator = new Even;
    }

    /**
     * @dataProvider providerForEven
     */
    public function testEvenNumbersShouldPass($input)
    {
        $this->assertTrue($this->evenValidator->validate($input));
        $this->assertTrue($this->evenValidator->check($input));
        $this->assertTrue($this->evenValidator->assert($input));
    }

    /**
     * @dataProvider providerForNotEven
     * @expectedException \rock\validation\exceptions\EvenException
     */
    public function testNotEvenNumbersShouldFail($input)
    {
        $this->assertFalse($this->evenValidator->validate($input));
        $this->assertFalse($this->evenValidator->assert($input));
    }

    public function providerForEven()
    {
        return array(
            array(''),
            array(-2),
            array(-0),
            array(0),
            array(32),
        );
    }

    public function providerForNotEven()
    {
        return array(
            array(-5),
            array(-1),
            array(1),
            array(13),
        );
    }
}

