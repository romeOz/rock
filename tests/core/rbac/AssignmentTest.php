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
class AssignmentTest extends DatabaseTestCase
{
    use CommonTestTrait;

    /** @var  RBAC */
    protected $rbac;

    public function setUp()
    {
        parent::setUp();
        static::sessionUp();
        
        $this->rbac = new PhpManager([
             'path' => '@rockunit/data/rbac/roles.php',
             'pathAssignments' => '@rockunit/data/rbac/assignments.php'
        ]);
    }

    public function tearDown()
    {
        parent::tearDown();
        static::sessionDown();
    }

    public function testGet()
    {
        $this->assertSame(['editor'], $this->rbac->getAssignments(2));
    }

    public function testGetNull()
    {
        $this->assertSame([], $this->rbac->getAssignments(77));
    }

    public function testAssign()
    {
        $moderator = $this->rbac->getRole('moderator');
        $this->assertTrue($this->rbac->assign(2, [$moderator]));
        $expected = $this->rbac->getAssignments(2);
        sort($expected);
        $actual = ['editor', 'moderator'];
        sort($actual);

        $this->assertEquals($expected, $actual);
        $this->assertTrue($this->rbac->revoke(2, [$moderator]));
        $this->assertEquals(['editor'], $this->rbac->getAssignments(2));
    }

    /**
     * @expectedException \rock\rbac\RBACException
     */
    public function testAssignThrowException()
    {
        $update_post = $this->rbac->getPermission('update_post');
        $moderator = $this->rbac->getRole('moderator');

        $this->assertTrue($this->rbac->assign(2, [$update_post, $moderator]));
    }

    /**
     * @expectedException \rock\rbac\RBACException
     */
    public function testAssignDuplicateThrowException()
    {
        $editor = $this->rbac->getRole('editor');
        $this->assertTrue($this->rbac->assign(2, [$editor]));
    }

    public function testCheckFalse()
    {
        Rock::$app->user->add('id', 3);
        $this->assertFalse($this->rbac->check(3, 'wizard'));
        $moderator = $this->rbac->getRole('moderator');
        $this->assertTrue($this->rbac->assign(3, [$moderator]));
        $this->assertEquals(['moderator'], $this->rbac->getAssignments(3));
        $this->assertFalse($this->rbac->check(3, 'moderator'));
        $this->assertTrue($this->rbac->revoke(3, [$moderator]));
        $this->assertEquals([], $this->rbac->getAssignments(3));
        $this->assertFalse($this->rbac->check(3, 'moderator'));
    }

    public function testCheckTrue()
    {
        Rock::$app->user->add('id', 3);
        $this->assertFalse($this->rbac->check(3, 'editor'));
        $editor = $this->rbac->getRole('editor');
        $this->rbac->assign(3, [$editor]);
        $this->assertFalse($this->rbac->check(3, 'editor'));

        Rock::$app->user->add('is_login', 1);
        $this->assertTrue($this->rbac->check(3, 'editor'));

        $this->assertTrue($this->rbac->check(3, 'update_post'));
        $this->assertFalse($this->rbac->check(3, 'delete_post'));
        $this->rbac->revoke(3, [$editor]);
        $this->assertFalse($this->rbac->check(3, 'update_post'));
        $this->assertFalse($this->rbac->check(3, 'editor'));
    }

    public function testCheckMultiAssignTrue()
    {
        Rock::$app->user->add('id', 3);
        $this->assertFalse($this->rbac->check(3, 'editor'));
        $editor = $this->rbac->getRole('editor');
        $admin = $this->rbac->getRole('admin');
        $this->rbac->assign(3, [$editor, $admin]);
        $this->assertFalse($this->rbac->check(3, 'editor'));
        $this->assertFalse($this->rbac->check(3, 'delete_post'));
        Rock::$app->user->add('is_login', 1);
        $this->assertTrue($this->rbac->check(3, 'editor'));
        $this->assertTrue($this->rbac->check(3, 'update_post'));
        $this->assertTrue($this->rbac->check(3, 'delete_post'));
        $this->rbac->revoke(3, [$editor,$admin]);
        $this->assertFalse($this->rbac->check(3, 'update_post'));
        $this->assertFalse($this->rbac->check(3, 'editor'));
        $this->assertFalse($this->rbac->check(3, 'delete_post'));
    }


    public function testParams()
    {
        Rock::$app->user->add('id', 3);
        $bar = new TestParams();
        $bar->name = 'bar';
        $this->assertTrue($this->rbac->add($bar));
        $this->assertTrue($this->rbac->has('bar'));

        $this->assertFalse($this->rbac->check(3, 'bar'));
        $this->assertTrue($this->rbac->assign(3, [$bar]));
        $this->assertTrue($this->rbac->check(3, 'bar', ['test' => 'test']));
        $this->expectOutputRegex('/test$/');
        //$this->rbac->revoke(3, [$bar]);
        $this->rbac->remove('bar');
    }
}

class NotGuestPermission extends Permission
{
    public function execute(array $params = null)
    {
        return !Rock::$app->user->isGuest();
    }
}


class TestParams extends Role
{
    public function execute(array $params = null)
    {
        echo $params['test'];
        return true;
    }
}
 