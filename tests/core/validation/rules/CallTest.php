<?php
namespace rockunit\core\validation;

use rock\validation\rules\Arr;
use rock\validation\rules\Call;

class CallTest extends \PHPUnit_Framework_TestCase
{
    public function thisIsASampleCallbackUsedInsideThisTest()
    {
        return array();
    }

    public function testCallbackValidatorShouldAcceptEmptyString()
    {
        $v = new Call('str_split', new Arr);
        $this->assertTrue($v->assert(''));
    }

    public function testCallbackValidatorShouldAcceptStringWithFunctionName()
    {
        $v = new Call('str_split', new Arr);
        $this->assertTrue($v->assert('test'));
    }

    public function testCallbackValidatorShouldAcceptArrayCallbackDefinition()
    {
        $v = new Call(array($this, 'thisIsASampleCallbackUsedInsideThisTest'), new Arr);
        $this->assertTrue($v->assert('test'));
    }

    public function testCallbackValidatorShouldAcceptClosures()
    {
        $v = new Call(function() {
                    return array();
                }, new Arr);
        $this->assertTrue($v->assert('test'));
    }

    /**
     * @expectedException \rock\validation\exceptions\CallException
     */
    public function testCallbackFailedShouldThrowCallException()
    {
        $v = new Call('strrev', new Arr);
        $this->assertFalse($v->validate('test'));
        $this->assertFalse($v->assert('test'));
    }
}

