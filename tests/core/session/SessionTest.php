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
            Rock::$app->session->getAll(),
            ['id' => 1, 'title' => 'text3', 'params' => ['param_1', 'param_2']]
        );
        $this->assertSame(Rock::$app->session->getAll(['title', 'params'], ['params']), ['title' => 'text3']);
    }

    public function testGetIterator()
    {
        $_SESSION = ['id' => 1, 'title' => 'text3', 'params' => ['param_1', 'param_2']];
        $this->assertSame(Rock::$app->session->getIterator([], ['title'])->current(), 1);
        $this->assertSame(
            Rock::$app->session->getIterator([], ['title'])->getArrayCopy(),
            ['id' => 1, 'params' => ['param_1', 'param_2']]
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
            Rock::$app->session->getAll(),
            ['id' => 1, 'title' => 'text3', 'params' => ['param_1', 'param_2']]
        );

        Rock::$app->session->add(['params', 1], 'change');
        $this->assertSame(
            Rock::$app->session->getAll(),
            ['id' => 1, 'title' => 'text3', 'params' => ['param_1', 'change']]
        );
        Rock::$app->session->add('params.0', 'change');
        $this->assertSame(
            Rock::$app->session->getAll(),
            ['id' => 1, 'title' => 'text3', 'params' => ['change', 'change']]
        );
    }

    public function testRemove()
    {
        $_SESSION = ['id' => 1, 'title' => 'text3', 'params' => ['param_1', 'param_2']];
        Rock::$app->session->remove('params.1');
        $this->assertSame(Rock::$app->session->getAll(), ['id' => 1, 'title' => 'text3', 'params' => ['param_1']]);
        Rock::$app->session->removeMulti(['id', 'params']);
        $this->assertSame(Rock::$app->session->getAll(), ['title' => 'text3']);
    }

    public function testSetFlash()
    {
        Rock::$app->session->setFlash('flash_1', 'text');
        $this->assertSame(Rock::$app->session->getFlash('flash_1'), 'text');
        $this->assertSame(
            Rock::$app->session->getAll(),
            array(
                'flash_1' => 'text',
                '__flash' =>
                    array(
                        'flash_1' => 0,
                    ),
            )
        );
    }

    public function testGetAllFlashes()
    {
        Rock::$app->session->setFlash('flash_1', 'text');
        Rock::$app->session->setFlash('flash_2');
        $this->assertSame(
            Rock::$app->session->getAllFlashes(),
            array(
                'flash_1' => 'text',
                'flash_2' => true,
            )
        );
        Rock::$app->session->removeFlash('flash_2');
        $this->assertSame(
            Rock::$app->session->getAllFlashes(),
            array(
                'flash_1' => 'text',
            )
        );
        Rock::$app->session->removeAllFlashes();
        $this->assertSame(Rock::$app->session->getAllFlashes(), []);
    }
}
 