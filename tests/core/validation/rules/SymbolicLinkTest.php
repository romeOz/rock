<?php

namespace rockunit\core\validation;

use rock\validation\rules\SymbolicLink;

$GLOBALS['is_link'] = null;

function is_link($link)
{
    $return = \is_link($link);
    if (null !== $GLOBALS['is_link']) {
        $return             = $GLOBALS['is_link'];
        $GLOBALS['is_link'] = null;
    }

    return $return;
}

class SymbolicLinkTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers \rock\validation\rules\SymbolicLink::validate
     */
    public function testValidSymbolicLinkShouldReturnTrue()
    {
        $GLOBALS['is_link'] = true;

        $rule = new SymbolicLink();
        $input = __DIR__ . '/../src/valid/readable/link.lnk';
        $this->assertTrue($rule->validate($input));
    }

    /**
     * @covers \rock\validation\rules\SymbolicLink::validate
     */
    public function testInvalidSymbolicLinkShouldThrowException()
    {
        $GLOBALS['is_link'] = false;

        $rule = new SymbolicLink();
        $input = __DIR__ . '/../src/valid/readable/file.txt';
        $this->assertFalse($rule->validate($input));
    }

    /**
     * @covers \rock\validation\rules\SymbolicLink::validate
     */
    public function testShouldValidateObjects()
    {
        $rule = new SymbolicLink();
        $object = $this->getMock('SplFileInfo', array('isLink'), array('somelink.lnk'));
        $object->expects($this->once())
                ->method('isLink')
                ->will($this->returnValue(true));

        $this->assertTrue($rule->validate($object));
    }

}
