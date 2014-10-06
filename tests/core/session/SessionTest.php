<?php

namespace rockunit\core\session;


use rock\Rock;
use rockunit\common\CommonTrait;

/**
 * @group base
 */
class SessionTest extends \PHPUnit_Framework_TestCase
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

    protected function removeFlash(array $data)
    {
        unset($data['__flash']);
        return $data;
    }

    /**
     * @dataProvider providerGet
     */
    public function testGet($expected, $actual, $keys, $default = null)
    {
        $_SESSION = $expected;
        $this->assertSame(Rock::$app->session->get($keys, $default), $actual);
    }

    public function providerGet()
    {
        return [
            [
                ['id' => 1, 'title' => 'text3', 'params' => ['param_1', 'param_2']],
                'text3',
                'title'
            ],
            [
                ['id' => 1, 'title' => 'text3', 'params' => ['param_1', 'param_2']],
                'param_1',
                'params.0'
            ],
            [
                ['id' => 1, 'title' => 'text3', 'params' => ['param_1', 'param_2']],
                'param_1',
                ['params', 0]
            ],
            [
                ['id' => 1, 'title' => 'text3', 'params' => ['param_1', 'param_2']],
                'param_1',
                ['params', '0']
            ],
            [
                ['id' => 1, 'title' => 'text3', 'params' => ['param_1', 'param_2']],
                null,
                ['params', '0', 1]
            ],
            [
                ['id' => 1, 'title' => 'text3', 'params' => ['param_1', 'param_2']],
                'default',
                ['params', '0', 1],
                'default'
            ],

            [
                ['id' => 1, 'title' => 'text3', 'params' => ['param_1', 'param_2']],
                ['param_1', 'param_2'],
                ['params']
            ],
        ];
    }


    public function testGetMulti()
    {
        $_SESSION= ['id' => 1, 'title' => 'text3', 'params' => ['param_1', 'param_2']];
        $this->assertSame(
            Rock::$app->session->getMulti(['title', ['params', 1]]),
            ['title' => 'text3', 'params.1' => 'param_2']
        );
    }

    public function testToArray()
    {
        $_SESSION = ['id' => 1, 'title' => 'text3', 'params' => ['param_1', 'param_2']];
        $this->assertSame(
            ['id' => 1, 'title' => 'text3', 'params' => ['param_1', 'param_2']],
            Rock::$app->session->getAll([], ['__flash'])
        );
        $this->assertSame(['title' => 'text3'], Rock::$app->session->getAll(['title', 'params'], ['params', '__flash']));
    }

    public function testGetIterator()
    {
        $_SESSION = ['id' => 1, 'title' => 'text3', 'params' => ['param_1', 'param_2']];
        $this->assertSame(1, Rock::$app->session->getIterator([], ['title'])->current());
        $this->assertSame(
            ['id' => 1, 'params' => ['param_1', 'param_2']],
            $this->removeFlash(Rock::$app->session->getIterator([], ['title'])->getArrayCopy())
        );
    }

    public function testHasTrue()
    {
        $_SESSION = ['id' => 1, 'title' => 'text3', 'params' => ['param_1', 'param_2']];
        $this->assertTrue(Rock::$app->session->has('id'));
        $this->assertTrue(Rock::$app->session->has('params.1'));
        $this->assertTrue(Rock::$app->session->has(['params', 1]));
    }

    public function testHasFalse()
    {
        $_SESSION = ['id' => 1, 'title' => 'text3', 'params' => ['param_1', 'param_2']];
        $this->assertFalse(Rock::$app->session->has('test'));
        $this->assertFalse(Rock::$app->session->has('params.77'));
        $this->assertFalse(Rock::$app->session->has(['params', 77]));
    }

    public function testAdd()
    {
        Rock::$app->session->addMulti(['id' => 1, 'title' => 'text3', 'params' => ['param_1', 'param_2']]);
        $this->assertSame(
            ['id' => 1, 'title' => 'text3', 'params' => ['param_1', 'param_2']],
            $this->removeFlash(Rock::$app->session->getAll())
        );

        Rock::$app->session->add(['params', 1], 'change');
        $this->assertSame(
            ['id' => 1, 'title' => 'text3', 'params' => ['param_1', 'change']],
            $this->removeFlash(Rock::$app->session->getAll())
        );
        Rock::$app->session->add('params.0', 'change');
        $this->assertSame(
            ['id' => 1, 'title' => 'text3', 'params' => ['change', 'change']],
            $this->removeFlash(Rock::$app->session->getAll())
        );
    }

    public function testRemove()
    {
        $_SESSION = ['id' => 1, 'title' => 'text3', 'params' => ['param_1', 'param_2']];
        $session =Rock::$app->session;
        $session->remove('params.1');
        $this->assertSame(['id' => 1, 'title' => 'text3', 'params' => ['param_1']], $this->removeFlash($session->getAll()));
        Rock::$app->session->removeMulti(['id', 'params']);
        $this->assertSame(['title' => 'text3'], $this->removeFlash(Rock::$app->session->getAll()));
    }

    public function testSetFlash()
    {
        $session = Rock::$app->session;
        $session->setFlash('flash_1', 'text');
        $this->assertSame($session->getFlash('flash_1'), 'text');
        $this->assertSame(
            array(
                'flash_1' => 'text',
            ),
            $this->removeFlash($session->getAll())
        );
    }

    public function testGetAllFlashes()
    {
        $session = Rock::$app->session;
        $session->setFlash('flash_1', 'text');
        $session->setFlash('flash_2');
        $this->assertSame(
            array(
                'flash_1' => 'text',
                'flash_2' => true,
            ),
            $session->getAllFlashes()
        );
        $session->removeFlash('flash_2');
        $this->assertSame(
            array(
                'flash_1' => 'text',
            ),
            $session->getAllFlashes()
        );
        $session->removeAllFlashes();
        $this->assertSame([], $session->getAllFlashes());
    }
}
 