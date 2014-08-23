<?php

namespace rockunit\core\helpers;;


use rock\helpers\Helper;

/**
 * @group base
 * @group helpers
 */
class HelperTest extends \PHPUnit_Framework_TestCase
{
    public function testToType()
    {
        $this->assertSame(Helper::toType('null'), null);
        $this->assertSame(Helper::toType('true'), true);
        $this->assertSame(Helper::toType('false'), false);
        $this->assertSame(Helper::toType('0'), 0);
        $this->assertSame(Helper::toType(''), '');
        $this->assertSame(Helper::toType('foo'), 'foo');
        $this->assertSame(Helper::toType(null), null);
    }

    public function testHash()
    {
        $this->assertSame(Helper::hash('foo'), md5('foo'));
        $this->assertSame(Helper::hash(['foo']), md5(serialize(['foo'])));
        $this->assertSame(Helper::hash(['foo'], Helper::SERIALIZE_JSON), md5(json_encode(['foo'])));
    }
}
 