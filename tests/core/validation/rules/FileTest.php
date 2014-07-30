<?php

namespace rockunit\core\validation;

use rock\validation\rules\File;

$GLOBALS['is_file'] = null;

function is_file($file)
{
    $return = \is_file($file); // Running the real function
    if (null !== $GLOBALS['is_file']) {
        $return             = $GLOBALS['is_file'];
        $GLOBALS['is_file'] = null;
    }

    return $return;
}

class FileTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers \rock\validation\rules\File::validate
     */
    public function testValidFileShouldReturnTrue()
    {
        $GLOBALS['is_file'] = true;

        $rule = new File();
        $input = __DIR__ . '/../src/valid/readable/file.txt';
        $this->assertTrue($rule->validate($input));
    }

    /**
     * @covers \rock\validation\rules\File::validate
     */
    public function testInvalidFileShouldReturnFalse()
    {
        $GLOBALS['is_file'] = false;

        $rule = new File();
        $input = __DIR__ . '/../src/invalid/readable/file.txt';
        $this->assertFalse($rule->validate($input));
    }

    /**
     * @covers \rock\validation\rules\File::validate
     */
    public function testShouldValidateObjects()
    {
        $rule = new File();
        $object = $this->getMock('SplFileInfo', array('isFile'), array('somefile.txt'));
        $object->expects($this->once())
                ->method('isFile')
                ->will($this->returnValue(true));

        $this->assertTrue($rule->validate($object));
    }

}
