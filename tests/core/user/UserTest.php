<?php

namespace rockunit\core\user;

use rock\rbac\PhpManager;
use rock\rbac\RBAC;
use rock\Rock;
use rockunit\common\CommonTrait;

/**
 * @group base
 * @group rbac
 */
class UserTest extends \PHPUnit_Framework_TestCase
{
    use CommonTrait;

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
               'path' => '@tests/core/rbac/src/rbac.php',
               'pathAssignments' => '@tests/core/rbac/src/assignments.php'
           ]);
        return $rbac;
    }

    public function testGet()
    {
        Rock::$app->user->add('name', 'Smith');
        Rock::$app->user->add('id', 1);
        $this->assertSame(Rock::$app->user->get('name'), 'Smith');
    }


    public function testGetNull()
    {
        $this->assertNull(Rock::$app->user->get('name'));
        $this->assertSame(Rock::$app->user->getCount(), 0);
    }

    public function testGetMulti()
    {
        Rock::$app->user->addMulti(['id' => 1,'name' => 'Smith', 'age' => 21, 'gender' => 'male', 'groups.0' => 'group_1', 'groups.1' => 'group_2']);
        $this->assertSame(
            Rock::$app->user->getMulti(['name', 'firstname', 'gender', 'groups']),
            [
                'name' => 'Smith',
                'gender' => 'male',
                'groups' => ['group_1', 'group_2']
            ]
        );
    }

    public function testRemoveMulti()
    {
        Rock::$app->user->addMulti(['id' => 1,'name' => 'Smith', 'age' => 21, 'gender' => 'male', 'groups' => ['group_1', 'group_2']]);
        Rock::$app->user->removeMulti(['name', 'gender', 'groups.1']);
        $this->assertSame(Rock::$app->user->getCount(), 3);
        $this->assertSame(Rock::$app->user->getAll(), ['id' => 1,'age' => 21, 'groups' => ['group_1']]);
    }


    public function testHasTrue()
    {
        Rock::$app->user->addMulti(['id' => 1,'name' => 'Smith', 'age' => 21, 'gender' => 'male', 'groups' => ['group_1', 'group_2']]);
        $this->assertTrue(Rock::$app->user->has('age'));
        $this->assertTrue(Rock::$app->user->has(['groups', 1]));
        $this->assertTrue(Rock::$app->user->has('groups.1'));
    }


    public function testHasFalse()
    {
        Rock::$app->user->addMulti(['id' => 1,'name' => 'Smith', 'age' => 21, 'gender' => 'male']);
        Rock::$app->user->remove('age');
        $this->assertFalse(Rock::$app->user->has('age'));
        $this->assertFalse(Rock::$app->user->has(['groups', 77]));
        $this->assertFalse(Rock::$app->user->has('groups.77'));
    }


    public function testIsAuthenticatedTrue()
    {
        Rock::$app->user->add('id', 1);
        Rock::$app->user->add('is_login', 1);
        $this->assertTrue(Rock::$app->user->isAuthenticated());
    }


    public function testIsAuthenticatedFalse()
    {
        Rock::$app->user->add('id', 1);
        Rock::$app->user->add('is_login', 0);
        $this->assertFalse(Rock::$app->user->isAuthenticated());
    }


    public function testIsAuthenticatedWithoutSessionFalse()
    {
        $this->assertFalse(Rock::$app->user->isAuthenticated());
    }

    public function testCheckAccessEmptyFalse()
    {
        Rock::$app->user->addMulti(['id' => 3, 'name' => 'Smith', 'age' => 21, 'gender' => 'male']);
        $this->assertFalse(Rock::$app->user->check('moderator',  null, false));
    }

    public function testCheckAccess()
    {
        Rock::$app->user->addMulti(['id' => 3, 'name' => 'Smith', 'age' => 21, 'gender' => 'male']);
        $moderator = $this->getRBAC()->getRole('moderator');
        $editor = $this->getRBAC()->getRole('editor');
        $this->assertTrue($this->getRBAC()->assign(3, [$moderator, $editor]));
        $this->assertFalse(Rock::$app->user->check('editor', null, false));
        $this->assertFalse(Rock::$app->user->check('update_post', null, false));
        $this->assertFalse(Rock::$app->user->check('delete_post',null, false));

        Rock::$app->user->add('is_login', 1);

        $this->assertTrue(Rock::$app->user->check('editor', null, false));
        $this->assertTrue(Rock::$app->user->check('update_post', null, false));
        $this->assertFalse(Rock::$app->user->check('delete_post', null, false));
        Rock::$app->user->add('is_login', 0);
        $this->assertFalse(Rock::$app->user->check('editor', null, false));
        $this->assertFalse(Rock::$app->user->check('update_post', null, false));
        $this->assertFalse(Rock::$app->user->check('delete_post', null, false));
        $this->assertTrue($this->getRBAC()->revoke(3, [$moderator, $editor]));
    }
}
 