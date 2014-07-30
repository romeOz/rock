<?php
namespace rockunit\core\validation;

use rock\validation\rules\MacAddress;

class MacAddressTest extends \PHPUnit_Framework_TestCase
{
    /** @var MacAddress */
    protected $macaddressValidator;

    protected function setUp()
    {
        $this->macaddressValidator = new MacAddress;
    }

    /**
     * @dataProvider providerForMacAddress
     *
     */
    public function testValidMacaddressesShouldReturnTrue($input)
    {
        $this->assertTrue($this->macaddressValidator->__invoke($input));
        $this->assertTrue($this->macaddressValidator->assert($input));
        $this->assertTrue($this->macaddressValidator->check($input));
    }

    /**
     * @dataProvider providerForNotMacAddress
     * @expectedException \rock\validation\exceptions\MacAddressException
     */
    public function testInvalidMacaddressShouldThrowMacAddressException($input)
    {
        $this->assertFalse($this->macaddressValidator->__invoke($input));
        $this->assertFalse($this->macaddressValidator->assert($input));
    }

    public function providerForMacAddress()
    {
        return array(
            array(''),
            array('00:11:22:33:44:55'),
            array('66-77-88-99-aa-bb'),
            array('AF:0F:bd:12:44:ba'),
            array('90-bc-d3-1a-dd-cc'),
        );
    }

    public function providerForNotMacAddress()
    {
        return array(
            array('00-1122:33:44:55'),
            array('66-77--99-jj-bb'),
            array('HH:0F-bd:12:44:ba'),
            array('90-bc-nk:1a-dd-cc'),
        );
    }
}

