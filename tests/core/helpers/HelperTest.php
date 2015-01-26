<?php

namespace rockunit\core\helpers;;


use rock\helpers\Helper;

/**
 * @group base
 * @group helpers
 */
class HelperTest extends \PHPUnit_Framework_TestCase
{
    public function testGetValue()
    {
        $array = [];
        $this->assertNull(Helper::getValue($array['foo']['bar']));
        $this->assertSame('test', Helper::getValue($array['foo']['bar'], 'test'));
        $this->assertSame('test', Helper::getValue($array['foo']['bar'], 'test', true));

        $array['foo']['bar'] = 0;
        $this->assertSame('test', Helper::getValue($array['foo']['bar'], 'test'));
        $this->assertSame(0, Helper::getValue($array['foo']['bar'], 'test', true));
    }

    public function testUpdate()
    {
        $array = [];
        $this->assertNull(Helper::update($array['foo']['bar'], function(){}));
        $this->assertSame('test', Helper::update($array['foo']['bar'], function(){}, 'test'));
        $this->assertSame('test', Helper::update($array['foo']['bar'], function(){}, 'test', true));

        $array['foo']['bar'] = 0;
        $this->assertSame('test', Helper::update($array['foo']['bar'], function(){}, 'test'));
        $this->assertSame(7, Helper::update($array['foo']['bar'], function(){return 7;}, 'test', true));
    }

    public function testToType()
    {
        $this->assertNull(Helper::toType('null'));
        $this->assertNull(Helper::toType(null));
        $this->assertSame(true, Helper::toType('true'));
        $this->assertSame(false, Helper::toType('false'));
        $this->assertSame(0, Helper::toType('0'));
        $this->assertSame('', Helper::toType(''));
        $this->assertSame('foo', Helper::toType('foo'));
    }

    public function testClearByType()
    {
        $this->assertNull(Helper::clearByType(null));
        $this->assertSame([], Helper::clearByType(['foo', 'test', 'bar']));
        $this->assertSame(0, Helper::clearByType(7));
        $this->assertSame(0.0, Helper::clearByType(7.7));
        $this->assertSame(0.0, Helper::clearByType(7.7));
        $this->assertSame('', Helper::clearByType('test'));
    }
}