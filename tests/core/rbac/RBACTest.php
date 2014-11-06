<?php

namespace rockunit\core\rbac;

use rock\rbac\Permission;
use rock\rbac\PhpManager;
use rock\rbac\RBAC;
use rock\rbac\Role;
use rock\Rock;
use rockunit\common\CommonTrait;
use rockunit\core\db\DatabaseTestCase;

class NotGuestPermissionRBAC extends Permission
{
    public function execute(array $params = null)
    {
        return !Rock::$app->user->isGuest();
    }
}


class TestParamsRBAC extends Role
{
    public function execute(array $params = null)
    {
        echo $params['test'];
        return true;
    }
}

/**
 * @group rbac
 */
class RBACTest extends DatabaseTestCase
{
    use CommonTrait;

    /** @var  RBAC */
    protected $rbac;
    
    public function setUp()
    {
        parent::setUp();
        static::sessionUp();
    }

    public function tearDown()
    {
        parent::tearDown();
        static::sessionDown();
    }

    /**
     * @return RBAC
     */
    protected function getRBAC()
    {
        $rbac = new PhpManager([
               'path' => '@tests/core/rbac/src/rbac.php',
               'pathAssignments' => '@tests/core/rbac/src/assignments.php'
           ]);
        return $rbac;
    }

    public function testGetSuccess()
    {
        $this->assertTrue($this->getRBAC()->getRole('guest') instanceof Role);
        $this->assertTrue($this->getRBAC()->get('read_post') instanceof Permission);
        $this->assertTrue($this->getRBAC()->getPermission('read_post') instanceof Permission);
    }


    public function testGetNull()
    {
        $this->assertNull($this->getRBAC()->get('test'));
    }

    /**
     * @expectedException \rock\rbac\RBACException
     */
    public function testGetPermissionThrowException()
    {
        $this->getRBAC()->getPermission('guest');
    }

    /**
     * @expectedException \rock\rbac\RBACException
     */
    public function testGetRoleThrowException()
    {
        $this->getRBAC()->getRole('read_post');
    }


    public function testAddMultiCheckAccessFalse()
    {
        $bar = new NotGuestPermissionRBAC;
        $bar->name = 'bar';
        $this->getRBAC()->add($bar);
        $baz = $this->getRBAC()->createPermission('baz');
        $this->getRBAC()->add($baz);
        $foo = $this->getRBAC()->createRole('foo');
        $this->getRBAC()->add($foo);
        $this->getRBAC()->attachItems($foo,[$bar, $baz]);

        $this->assertTrue($this->getRBAC()->hasChild($foo, $bar->name));
        $this->assertTrue($this->getRBAC()->hasChildren($foo, [$baz->name]));
        $this->assertTrue($this->getRBAC()->has('foo'));
        $this->assertTrue($this->getRBAC()->has('bar'));
        $this->assertTrue($this->getRBAC()->has('baz'));
        //$this->assertFalse($this->getRBAC()->check('foo'));
        $this->assertTrue($this->getRBAC()->removeMulti(['foo', 'bar', 'baz']));
        $this->assertFalse($this->getRBAC()->has('foo'));
        $this->assertFalse($this->getRBAC()->has('bar'));
        $this->assertFalse($this->getRBAC()->has('baz'));
        $this->assertFalse($this->getRBAC()->hasChild($foo, $bar->name));
        $this->assertFalse($this->getRBAC()->hasChildren($foo, [$baz->name]));
    }

    public function testDetachLoop()
    {
        $bar = new NotGuestPermissionRBAC;
        $bar->name = 'bar';
        $this->getRBAC()->add($bar);
        $baz = $this->getRBAC()->createPermission('baz');
        $this->getRBAC()->add($baz);
        $foo = $this->getRBAC()->createRole('foo');
        $this->getRBAC()->add($foo);
        $this->getRBAC()->attachItems($foo,[$bar, $baz]);
        $test = $this->getRBAC()->createRole('test');
        $this->getRBAC()->add($test);
        $this->getRBAC()->attachItems($test,[$baz]);
        $this->assertTrue($this->getRBAC()->hasChild($foo, $baz->name));
        $this->assertTrue($this->getRBAC()->hasChild($test, $baz->name));
        $this->assertTrue($this->getRBAC()->removeMulti(['baz']));
        $this->assertFalse($this->getRBAC()->hasChild($foo, $baz->name));
        $this->assertFalse($this->getRBAC()->hasChild($test, $baz->name));
        $this->assertTrue($this->getRBAC()->removeMulti(['foo', 'bar', 'test', 'baz']));
    }

    public function testAddMultiCheckAccessTrue()
    {
        $bar = new NotGuestPermissionRBAC;
        $bar->name = 'bar';
        $this->getRBAC()->add($bar);
        $baz = $this->getRBAC()->createPermission('baz');
        $this->getRBAC()->add($baz);
        $foo = $this->getRBAC()->createRole('foo');
        $this->getRBAC()->add($foo);
        $this->getRBAC()->attachItems($foo,[$bar, $baz]);
        $this->assertTrue($this->getRBAC()->has('foo'));
        $this->assertTrue($this->getRBAC()->has('bar'));
        $this->assertTrue($this->getRBAC()->has('baz'));

        $this->assertTrue($this->getRBAC()->detachItems($foo, [$bar]));
        //$this->assertTrue($this->getRBAC()->check('foo'));
        $this->getRBAC()->removeMulti(['foo', 'bar', 'baz']);
        $this->assertFalse($this->getRBAC()->has('foo'));
        $this->assertFalse($this->getRBAC()->has('bar'));
        $this->assertFalse($this->getRBAC()->has('baz'));
    }

    public function testRemove()
    {
        $bar = $this->getRBAC()->createRole('bar');
        $this->getRBAC()->add($bar);
        $this->assertTrue($this->getRBAC()->has('bar'));
        $this->getRBAC()->remove('bar');
        $this->assertFalse($this->getRBAC()->has('bar'));
    }
}
 