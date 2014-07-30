<?php
namespace rockunit\core\validation;

use rock\validation\rules\Object;

class ObjectTest extends \PHPUnit_Framework_TestCase
{
    /** @var  Object */
    protected $object;

    protected function setUp()
    {
        $this->object = new Object;
    }

    /**
     * @dataProvider providerForObject
     *
     */
    public function testObject($input)
    {
        $this->assertTrue($this->object->__invoke($input));
        $this->assertTrue($this->object->assert($input));
        $this->assertTrue($this->object->check($input));
    }

    /**
     * @dataProvider providerForNotObject
     * @expectedException \rock\validation\exceptions\ObjectException
     */
    public function testNotObject($input)
    {
        $this->assertFalse($this->object->__invoke($input));
        $this->assertFalse($this->object->assert($input));
    }

    public function providerForObject()
    {
        return array(
            array(''),
            array(new \stdClass),
            array(new \ArrayObject),
        );
    }

    public function providerForNotObject()
    {
        return array(
            array(null),
            array(121),
            array(array()),
            array('Foo'),
            array(false),
        );
    }
}

