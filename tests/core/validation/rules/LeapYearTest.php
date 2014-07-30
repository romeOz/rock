<?php
namespace rockunit\core\validation;

use DateTime;
use rock\validation\rules\LeapYear;

class LeapYearTest extends \PHPUnit_Framework_TestCase
{
    /** @var  LeapYear */
    protected $leapYearValidator;

    protected function setUp()
    {
        $this->leapYearValidator = new LeapYear;
    }

    public function testValidLeapDate()
    {
        $this->assertTrue($this->leapYearValidator->__invoke(''));
        $this->assertTrue($this->leapYearValidator->__invoke('2008'));
        $this->assertTrue($this->leapYearValidator->__invoke('2008-02-29'));
        $this->assertTrue($this->leapYearValidator->__invoke(2008));
        $this->assertTrue($this->leapYearValidator->__invoke(
            new DateTime('2008-02-29')));
    }

    public function testInvalidLeapDate()
    {
        $this->assertFalse($this->leapYearValidator->__invoke('2009'));
        $this->assertFalse($this->leapYearValidator->__invoke('2009-02-29'));
        $this->assertFalse($this->leapYearValidator->__invoke(2009));
        $this->assertFalse($this->leapYearValidator->__invoke(
            new DateTime('2009-02-29')));
        $this->assertFalse($this->leapYearValidator->__invoke(array()));
    }
}

