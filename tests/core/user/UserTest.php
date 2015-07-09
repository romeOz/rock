<?php

namespace rockunit\core\user;

use rock\di\Container;
use rock\rbac\PhpManager;
use rock\rbac\RBAC;
use rock\user\User;
use rockunit\common\CommonTestTrait;

/**
 * @group base
 * @group rbac
 */
class UserTest extends \PHPUnit_Framework_TestCase
{
    use CommonTestTrait;

    public function setUp()
    {
        parent::setUp();
        static::sessionUp();
    }

    public function tearDown()
    {
        static::sessionDown();
    }

    /**
     * @return RBAC
     */
    protected function getRBAC()
    {
        $rbac = new PhpManager([
            'path' => '@rockunit/data/rbac/roles.php',
            'pathAssignments' => '@rockunit/data/rbac/assignments.php'
        ]);
        return $rbac;
    }

    /**
     * @return User
     */
    protected function getUser()
    {
        return Container::load(User::className());
    }

    // tests

    public function testGet()
    {
        $user = $this->getUser();
        $user->add('name', 'Smith');
        $user->add('id', 1);
        $this->assertSame('Smith', $user->get('name'));
    }

    public function testGetNull()
    {
        $user = $this->getUser();
        $this->assertNull($user->get('name'));
        $this->assertSame(0, $user->getCount());
    }

    public function testRemoveMulti()
    {
        $user = $this->getUser();
        $user->addMulti(['id' => 1,'name' => 'Smith', 'age' => 21, 'gender' => 'male', 'groups' => ['group_1', 'group_2']]);
        $user->removeMulti(['name', 'gender', 'groups.1']);
        $this->assertSame(3, $user->getCount());
        $this->assertSame(['id' => 1,'age' => 21, 'groups' => ['group_1']], $user->getAll());
    }

    public function testHasTrue()
    {
        $user = $this->getUser();
        $user->addMulti(['id' => 1,'name' => 'Smith', 'age' => 21, 'gender' => 'male', 'groups' => ['group_1', 'group_2']]);
        $this->assertTrue($user->exists('age'));
        $this->assertTrue($user->exists(['groups', 1]));
        $this->assertTrue($user->exists('groups.1'));
    }

    public function testHasFalse()
    {
        $user = $this->getUser();
        $user->addMulti(['id' => 1,'name' => 'Smith', 'age' => 21, 'gender' => 'male']);
        $user->remove('age');
        $this->assertFalse($user->exists('age'));
        $this->assertFalse($user->exists(['groups', 77]));
        $this->assertFalse($user->exists('groups.77'));
    }

    public function testIsAuthenticatedTrue()
    {
        $user = $this->getUser();
        $user->add('id', 1);
        $user->add('is_login', 1);
        $this->assertTrue($user->isLogged());
    }

    public function testIsAuthenticatedFalse()
    {
        $user = $this->getUser();
        $user->add('id', 1);
        $user->add('is_login', 0);
        $this->assertFalse($user->isLogged());
    }

    public function testIsAuthenticatedWithoutSessionFalse()
    {
        $user = $this->getUser();
        $this->assertFalse($user->isLogged());
    }

    public function testCheckAccessEmptyFalse()
    {
        $user = $this->getUser();
        $user->addMulti(['id' => 3, 'name' => 'Smith', 'age' => 21, 'gender' => 'male']);
        $this->assertFalse($user->check('moderator',  null, false));
    }

    public function testCheckAccess()
    {
        $user = $this->getUser();
        $user->addMulti(['id' => 3, 'name' => 'Smith', 'age' => 21, 'gender' => 'male']);
        $moderator = $this->getRBAC()->getRole('moderator');
        $editor = $this->getRBAC()->getRole('editor');
        $this->assertTrue($this->getRBAC()->assign(3, [$moderator, $editor]));
        $this->assertFalse($user->check('editor', null, false));
        $this->assertFalse($user->check('update_post', null, false));
        $this->assertFalse($user->check('delete_post',null, false));

        $user->add('is_login', 1);

        $this->assertTrue($user->check('editor', null, false));
        $this->assertTrue($user->check('update_post', null, false));
        $this->assertFalse($user->check('delete_post', null, false));
        $user->add('is_login', 0);
        $this->assertFalse($user->check('editor', null, false));
        $this->assertFalse($user->check('update_post', null, false));
        $this->assertFalse($user->check('delete_post', null, false));
        $this->assertTrue($this->getRBAC()->revoke(3, [$moderator, $editor]));
    }

    public function testReturnUrl()
    {
        $user = $this->getUser();
        $this->assertSame('http://site.com/', $user->getReturnUrl());

        // add
        $user->returnUrl = 'http://test.com/';
        $this->assertSame('http://test.com/', $user->getReturnUrl());
    }
}
 