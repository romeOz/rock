<?php

namespace rockunit\core\rbac;


use rock\rbac\Exception;
use rock\rbac\Permission;
use rock\rbac\PhpManager;
use rock\rbac\RBAC;
use rock\rbac\Role;
use rock\Rock;
use rockunit\common\CommonTrait;
use rockunit\core\db\DatabaseTestCase;

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

/**
 * @group rbac
 */
class AssignmentTest extends DatabaseTestCase
{
    use CommonTrait;

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

    public function testGet()
    {
        $this->assertSame($this->getRBAC()->getAssignments(2), ['editor']);
    }

    public function testGetNull()
    {
        $this->assertSame($this->getRBAC()->getAssignments(77), []);
    }

    public function testAssign()
    {
        $moderator = $this->getRBAC()->getRole('moderator');
        $this->assertTrue($this->getRBAC()->assign(2, [$moderator]));
        $expected = $this->getRBAC()->getAssignments(2);
        sort($expected);
        $actual = ['editor', 'moderator'];
        sort($actual);

        $this->assertEquals($expected, $actual);
        $this->assertTrue($this->getRBAC()->revoke(2, [$moderator]));
        $this->assertEquals($this->getRBAC()->getAssignments(2), ['editor']);
    }

    /**
     * @expectedException Exception
     */
    public function testAssignThrowException()
    {
        $update_post = $this->getRBAC()->getPermission('update_post');
        $moderator = $this->getRBAC()->getRole('moderator');

        $this->assertTrue($this->getRBAC()->assign(2, [$update_post, $moderator]));
    }

    /**
     * @expectedException Exception
     */
    public function testAssignDuplicateThrowException()
    {
        $editor = $this->getRBAC()->getRole('editor');
        $this->assertTrue($this->getRBAC()->assign(2, [$editor]));
    }

    public function testCheckFalse()
    {
        Rock::$app->user->add('id', 3);
        $this->assertFalse($this->getRBAC()->check(3, 'wizard'));
        $moderator = $this->getRBAC()->getRole('moderator');
        $this->assertTrue($this->getRBAC()->assign(3, [$moderator]));
        $this->assertEquals($this->getRBAC()->getAssignments(3), ['moderator']);
        $this->assertFalse($this->getRBAC()->check(3, 'moderator'));
        $this->assertTrue($this->getRBAC()->revoke(3, [$moderator]));
        $this->assertEquals($this->getRBAC()->getAssignments(3), []);
        $this->assertFalse($this->getRBAC()->check(3, 'moderator'));
    }

    public function testCheckTrue()
    {
        Rock::$app->user->add('id', 3);
        $this->assertFalse($this->getRBAC()->check(3, 'editor'));
        $editor = $this->getRBAC()->getRole('editor');
        $this->getRBAC()->assign(3, [$editor]);
        $this->assertFalse($this->getRBAC()->check(3, 'editor'));

        Rock::$app->user->add('is_login', 1);
        $this->assertTrue($this->getRBAC()->check(3, 'editor'));

        $this->assertTrue($this->getRBAC()->check(3, 'update_post'));
        $this->assertFalse($this->getRBAC()->check(3, 'delete_post'));
        $this->getRBAC()->revoke(3, [$editor]);
        $this->assertFalse($this->getRBAC()->check(3, 'update_post'));
        $this->assertFalse($this->getRBAC()->check(3, 'editor'));
    }

    public function testCheckMultiAssignTrue()
    {
        Rock::$app->user->add('id', 3);
        $this->assertFalse($this->getRBAC()->check(3, 'editor'));
        $editor = $this->getRBAC()->getRole('editor');
        $admin = $this->getRBAC()->getRole('admin');
        $this->getRBAC()->assign(3, [$editor, $admin]);
        $this->assertFalse($this->getRBAC()->check(3, 'editor'));
        $this->assertFalse($this->getRBAC()->check(3, 'delete_post'));
        Rock::$app->user->add('is_login', 1);
        $this->assertTrue($this->getRBAC()->check(3, 'editor'));
        $this->assertTrue($this->getRBAC()->check(3, 'update_post'));
        $this->assertTrue($this->getRBAC()->check(3, 'delete_post'));
        $this->getRBAC()->revoke(3, [$editor,$admin]);
        $this->assertFalse($this->getRBAC()->check(3, 'update_post'));
        $this->assertFalse($this->getRBAC()->check(3, 'editor'));
        $this->assertFalse($this->getRBAC()->check(3, 'delete_post'));
    }


    public function testParams()
    {
        Rock::$app->user->add('id', 3);
        $bar = new TestParams();
        $bar->name = 'bar';
        $this->assertTrue($this->getRBAC()->add($bar));
        $this->assertTrue($this->getRBAC()->has('bar'));

        $this->assertFalse($this->getRBAC()->check(3, 'bar'));
        $this->assertTrue($this->getRBAC()->assign(3, [$bar]));
        $this->assertTrue($this->getRBAC()->check(3, 'bar', ['test' => 'test']));
        $this->expectOutputRegex('/test$/');
        //$this->getRBAC()->revoke(3, [$bar]);
        $this->getRBAC()->remove('bar');
    }

}
 