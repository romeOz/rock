<?php

namespace rockunit\core\validation;

use rock\validation\rules\Exists;

$GLOBALS['file_exists'] = null;

function file_exists($file)
{
    $return = \file_exists($file); // Running the real function
    if (null !== $GLOBALS['file_exists']) {
        $return                 = $GLOBALS['file_exists'];
        $GLOBALS['file_exists'] = null;
    }

    return $return;
}

class ExistsTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers \rock\validation\rules\Exists::validate
     */
    public function testExistentFileShouldReturnTrue()
    {
        $GLOBALS['file_exists'] = true;

        $rule = new Exists();
        $input = __DIR__ . '/../src/valid/readable/file.txt';
        $this->assertTrue($rule->validate($input));
    }

    /**
     * @covers \rock\validation\rules\Exists::validate
     */
    public function testNonExistentFileShouldReturnFalse()
    {
        $GLOBALS['file_exists'] = false;

        $rule = new Exists();
        $input = __DIR__ . '/../src/invalid/readable/file.txt';
        $this->assertFalse($rule->validate($input));
    }

    /**
     * @covers \rock\validation\rules\Exists::validate
     */
    public function testShouldValidateObjects()
    {
        $GLOBALS['file_exists'] = true;

        $rule = new Exists();
        $input = __DIR__ . '/../src/valid/readable/file.txt';
        $object = new \SplFileInfo($input);

        $this->assertTrue($rule->validate($object));
    }

}
