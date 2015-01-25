<?php

namespace rockunit\core;

use rock\i18n\i18n;

class i18nTest extends \PHPUnit_Framework_TestCase
{
    protected static $buffer = [];

    public static function setUpBeforeClass()
    {
        $i18n = new i18n();
        $i18n->locale = 'en';
        static::$buffer = $i18n->getAll();
        $i18n->clear();
    }

    public static function tearDownAfterClass()
    {
        (new i18n())->addMulti(static::$buffer);
    }

    public function testAdd()
    {
        $i18n = new i18n();
        $i18n->locale('en')->category('lang');
        $i18n->add('foo.bar', 'text {{placeholder}}');
        $this->assertSame(
            [
                'lang' =>
                    [
                        'foo' =>
                            [
                                'bar' => 'text {{placeholder}}',
                            ],
                    ],
            ],
            $i18n->getAll()
        );
        $this->assertTrue($i18n->exists('foo.bar'));

        $this->assertSame('text', $i18n->translate('foo.bar'));

        // placeholder
        $this->assertSame('text baz', $i18n->translate('foo.bar', ['placeholder' => 'baz']));

        // not replace placeholder
        $this->assertSame('text {{placeholder}}', $i18n->removeBraces(false)->translate('foo.bar'));
    }

    public function testRemove()
    {
        $i18n = new i18n();
        $i18n->locale('en')->category('lang');
        $i18n->add('foo.bar', 'text');
        $i18n->remove('foo.bar');
        $this->assertSame(
            [
                'lang' => [
                        'foo' =>[],
                    ],
            ],
            $i18n->getAll()
        );
        $i18n->remove('foo');
        $this->assertSame(
            [
                'lang' => [],
            ],
            $i18n->getAll()
        );
        $this->assertFalse($i18n->exists('foo.bar'));
    }

    /**
     * @expectedException \rock\i18n\i18nException
     */
    public function testUnknown()
    {
        $i18n = new i18n();
        $i18n->locale('en')->category('lang');
        $i18n->translate('foo.bar');
    }
}