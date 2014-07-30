<?php
namespace rockunit\core\validation;

use rock\validation\rules\NoWhitespace;

class NoWhitespaceTest extends \PHPUnit_Framework_TestCase
{
    /** @var NoWhitespace  */
    protected $noWhitespaceValidator;

    protected function setUp()
    {
        $this->noWhitespaceValidator = new NoWhitespace;
    }

    /**
     * @dataProvider providerForPass
     */
    public function testStringWithNoWhitespaceShouldPass($input)
    {
        $this->assertTrue($this->noWhitespaceValidator->__invoke($input));
        $this->assertTrue($this->noWhitespaceValidator->check($input));
        $this->assertTrue($this->noWhitespaceValidator->assert($input));
    }

    /**
     * @dataProvider providerForFail
     * @expectedException \rock\validation\exceptions\NoWhitespaceException
     */
    public function testStringWithWhitespaceShouldFail($input)
    {
        $this->assertFalse($this->noWhitespaceValidator->__invoke($input));
        $this->assertFalse($this->noWhitespaceValidator->assert($input));
    }
    /**
     * @expectedException \rock\validation\exceptions\NoWhitespaceException
     */
    public function testStringWithLineBreaksShouldFail()
    {
        $this->assertFalse($this->noWhitespaceValidator->__invoke("w\npoiur"));
        $this->assertFalse($this->noWhitespaceValidator->assert("w\npoiur"));
    }

    public function providerForPass()
    {
        return array(
            array(''),
            array(0),
            array('wpoiur'),
            array('Foo'),
        );
    }

    public function providerForFail()
    {
        return array(
            array(' '),
            array('w poiur'),
            array('      '),
            array("Foo\nBar"),
            array("Foo\tBar"),
        );
    }
}
