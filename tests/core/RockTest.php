<?php

namespace rockunit\core;


use rock\Rock;

/**
 * @group base
 */
class RockTest extends \PHPUnit_Framework_TestCase 
{
    public $aliases;

    protected function setUp()
    {
        parent::setUp();
        $this->aliases = Rock::$aliases;
    }

    protected function tearDown()
    {
        parent::tearDown();
        Rock::$aliases = $this->aliases;
    }

    public function testAlias()
    {
        Rock::$aliases = [];
        $this->assertFalse(Rock::getAlias('@rock', [], false));

        Rock::setAlias('@rock', '/rock/framework');
        $this->assertEquals('/rock/framework', Rock::getAlias('@rock'));
        $this->assertEquals('/rock/framework/test/file', Rock::getAlias('@rock/test/file'));
        Rock::setAlias('@rock/runtime', '/rock/runtime');
        $this->assertEquals('/rock/framework', Rock::getAlias('@rock'));
        $this->assertEquals('/rock/framework/test/file', Rock::getAlias('@rock/test/file'));
        $this->assertEquals('/rock/runtime', Rock::getAlias('@rock/runtime'));
        $this->assertEquals('/rock/runtime/file', Rock::getAlias('@rock/runtime/file'));

        Rock::setAlias('@rock.test', '@rock/test');
        $this->assertEquals('/rock/framework/test', Rock::getAlias('@rock.test'));

        Rock::setAlias('@rock', null);
        $this->assertFalse(Rock::getAlias('@rock', [], false));
        $this->assertEquals('/rock/runtime/file', Rock::getAlias('@rock/runtime/file'));

        Rock::setAlias('@some/alias', '/www');
        $this->assertEquals('/www', Rock::getAlias('@some/alias'));

        // namespace
        Rock::setAlias('@rock.ns', '\rock\core');
        $this->assertEquals('\rock\core', Rock::getAlias('@rock.ns'));

        Rock::setAliases(['@web' => '/assets', '@app' => '/apps/common']);
        $this->assertEquals('/assets', Rock::getAlias('@web'));
        $this->assertEquals('/apps/common', Rock::getAlias('@app'));

        $this->setExpectedException(get_class(new \Exception));
        Rock::getAlias('@rock');
    }
}
 