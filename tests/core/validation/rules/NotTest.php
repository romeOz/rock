<?php
namespace rockunit\core\validation;

use rock\validation\rules\AllOf;
use rock\validation\rules\Digit;
use rock\validation\rules\Int;
use rock\validation\rules\Not;
use rock\validation\rules\NoWhitespace;
use rock\validation\Validation;

class NotTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider providerForValidNot
     *
     */
    public function testNot($v, $input)
    {
        $not = new Not($v);
        $this->assertTrue($not->assert($input));
    }

    public function testShortcutNot()
    {
        $this->assertTrue(Validation::int()->not()->assert('afg'));
    }

    /**
     * @dataProvider providerForInvalidNot
     * @expectedException \rock\validation\exceptions\ValidationException
     */
    public function testNotNotHaha($v, $input)
    {
        $not = new Not($v);
        $this->assertFalse($not->assert($input));
    }

    /**
     * @expectedException \rock\validation\exceptions\ValidationException
     */
    public function testShortcutNotNotHaha()
    {
        $this->assertFalse(Validation::int()->not()->assert(10));
    }

    public function providerForValidNot()
    {
        return array(
            array(new Int, 'aaa'),
            array(new AllOf(new NoWhitespace, new Digit), 'as df')
        );
    }

    public function providerForInvalidNot()
    {
        return array(
            array(new Int, ''),
            array(new Int, 123),
            array(new AllOf(new NoWhitespace, new Digit), '12 34')
        );
    }
}

