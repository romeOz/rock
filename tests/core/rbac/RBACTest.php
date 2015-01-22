<?php

namespace rockunit\core\rbac;

use rock\rbac\Permission;
use rock\rbac\PhpManager;
use rock\rbac\RBAC;
use rock\rbac\Role;
use rock\Rock;
use rockunit\common\CommonTestTrait;
use rockunit\core\db\DatabaseTestCase;

/**
 * @group rbac
 */
class RBACTest extends DatabaseTestCase
{
    use CommonTestTrait;

    /** @var  RBAC */
    protected $rbac;
    
    public function setUp()
    {
        parent::setUp();
        static::sessionUp();
        $this->rbac = new PhpManager([
             'path' => '@tests/core/rbac/src/rbac.php',
             'pathAssignments' => '@tests/core/rbac/src/assignments.php'
         ]);
    }

    public function tearDown()
    {
        parent::tearDown();
        static::sessionDown();
    }


    public function testGetSuccess()
    {
        $this->assertTrue($this->rbac->getRole('guest') instanceof Role);
        $this->assertTrue($this->rbac->get('read_post') instanceof Permission);
        $this->assertTrue($this->rbac->getPermission('read_post') instanceof Permission);
    }


    public function testGetNull()
    {
        $this->assertNull($this->rbac->get('test'));
    }

    /**
     * @expectedException \rock\rbac\RBACException
     */
    public function testGetPermissionThrowException()
    {
        $this->rbac->getPermission('guest');
    }

    /**
     * @expectedException \rock\rbac\RBACException
     */
    public function testGetRoleThrowException()
    {
        $this->rbac->getRole('read_post');
    }

    public function testAddMultiCheckAccessFalse()
    {
        $bar = new NotGuestPermissionRBAC;
        $bar->name = 'bar';
        $this->rbac->add($bar);
        $baz = $this->rbac->createPermission('baz');
        $this->rbac->add($baz);
        $foo = $this->rbac->createRole('foo');
        $this->rbac->add($foo);
        $this->rbac->attachItems($foo,[$bar, $baz]);

        $this->assertTrue($this->rbac->hasChild($foo, $bar->name));
        $this->assertTrue($this->rbac->hasChildren($foo, [$baz->name]));
        $this->assertTrue($this->rbac->has('foo'));
        $this->assertTrue($this->rbac->has('bar'));
        $this->assertTrue($this->rbac->has('baz'));
        //$this->assertFalse($this->rbac->check('foo'));
        $this->assertTrue($this->rbac->removeMulti(['foo', 'bar', 'baz']));
        $this->assertFalse($this->rbac->has('foo'));
        $this->assertFalse($this->rbac->has('bar'));
        $this->assertFalse($this->rbac->has('baz'));
        $this->assertFalse($this->rbac->hasChild($foo, $bar->name));
        $this->assertFalse($this->rbac->hasChildren($foo, [$baz->name]));
    }

    public function testDetachLoop()
    {
        $bar = new NotGuestPermissionRBAC;
        $bar->name = 'bar';
        $this->rbac->add($bar);
        $baz = $this->rbac->createPermission('baz');
        $this->rbac->add($baz);
        $foo = $this->rbac->createRole('foo');
        $this->rbac->add($foo);
        $this->rbac->attachItems($foo,[$bar, $baz]);
        $test = $this->rbac->createRole('test');
        $this->rbac->add($test);
        $this->rbac->attachItems($test,[$baz]);
        $this->assertTrue($this->rbac->hasChild($foo, $baz->name));
        $this->assertTrue($this->rbac->hasChild($test, $baz->name));
        $this->assertTrue($this->rbac->removeMulti(['baz']));
        $this->assertFalse($this->rbac->hasChild($foo, $baz->name));
        $this->assertFalse($this->rbac->hasChild($test, $baz->name));
        $this->assertTrue($this->rbac->removeMulti(['foo', 'bar', 'test', 'baz']));
    }

    public function testAddMultiCheckAccessTrue()
    {
        $bar = new NotGuestPermissionRBAC;
        $bar->name = 'bar';
        $this->rbac->add($bar);
        $baz = $this->rbac->createPermission('baz');
        $this->rbac->add($baz);
        $foo = $this->rbac->createRole('foo');
        $this->rbac->add($foo);
        $this->rbac->attachItems($foo,[$bar, $baz]);
        $this->assertTrue($this->rbac->has('foo'));
        $this->assertTrue($this->rbac->has('bar'));
        $this->assertTrue($this->rbac->has('baz'));

        $this->assertTrue($this->rbac->detachItems($foo, [$bar]));
        //$this->assertTrue($this->rbac->check('foo'));
        $this->rbac->removeMulti(['foo', 'bar', 'baz']);
        $this->assertFalse($this->rbac->has('foo'));
        $this->assertFalse($this->rbac->has('bar'));
        $this->assertFalse($this->rbac->has('baz'));
    }

    public function testRemove()
    {
        $bar = $this->rbac->createRole('bar');
        $this->rbac->add($bar);
        $this->assertTrue($this->rbac->has('bar'));
        $this->rbac->remove('bar');
        $this->assertFalse($this->rbac->has('bar'));
    }
}


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