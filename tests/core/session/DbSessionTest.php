<?php

namespace rockunit\core\session;


use rock\session\DbSession;
use rockunit\common\CommonTrait;
use rockunit\core\db\DatabaseTestCase;

/**
 * @group base
 * @group db
 */
class DbSessionTest extends DatabaseTestCase
{
    use CommonTrait;

    /** @var  DbSession */
    protected $handlerSession;
    protected function setUp()
    {
        parent::setUp();
        $this->handlerSession = new DbSession(['db' => $this->getConnection()]);
        $this->handlerSession->removeAll();
    }

    public function tearDown()
    {
        parent::tearDown();
        $this->handlerSession->removeAll();
    }

    /**
     * @dataProvider providerGet
     */
    public function testGet($expected, $actual, $keys, $default = null)
    {
        $this->handlerSession->addMulti($expected);
        $this->assertSame($this->handlerSession->get($keys, $default), $actual);
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
        $this->handlerSession->addMulti(['id' => 1, 'title' => 'text3', 'params' => ['param_1', 'param_2']]);
        $this->assertSame(
            $this->handlerSession->getMulti(['title', ['params', 1]]),
            ['title' => 'text3', 'params.1' => 'param_2']
        );
    }


    public function testGetAll()
    {
        $this->handlerSession->addMulti(['id' => 1, 'title' => 'text3', 'params' => ['param_1', 'param_2']]);
        $this->assertSame(
            $this->handlerSession->getAll(),
            ['id' => 1, 'title' => 'text3', 'params' => ['param_1', 'param_2']]
        );
        $this->assertSame($this->handlerSession->getAll(['title', 'params'], ['params']), ['title' => 'text3']);
    }

    public function testGetIterator()
    {
        $this->handlerSession->addMulti(['id' => 1, 'title' => 'text3', 'params' => ['param_1', 'param_2']]);
        $this->assertSame($this->handlerSession->getIterator([], ['title'])->current(), 1);
        $this->assertSame(
            $this->handlerSession->getIterator([], ['title'])->getArrayCopy(),
            ['id' => 1, 'params' => ['param_1', 'param_2']]
        );
    }

    public function testHasTrue()
    {
        $this->handlerSession->addMulti(['id' => 1, 'title' => 'text3', 'params' => ['param_1', 'param_2']]);
        $this->assertTrue($this->handlerSession->has('id'));
        $this->assertTrue($this->handlerSession->has('params.1'));
        $this->assertTrue($this->handlerSession->has(['params', 1]));
    }

    public function testHasFalse()
    {
        $this->handlerSession->addMulti(['id' => 1, 'title' => 'text3', 'params' => ['param_1', 'param_2']]);
        $this->assertFalse($this->handlerSession->has('test'));
        $this->assertFalse($this->handlerSession->has('params.77'));
        $this->assertFalse($this->handlerSession->has(['params', 77]));
    }

    public function testAdd()
    {
        $this->handlerSession->addMulti(['id' => 1, 'title' => 'text3', 'params' => ['param_1', 'param_2']]);
        $this->assertSame(
            $this->handlerSession->getAll(),
            ['id' => 1, 'title' => 'text3', 'params' => ['param_1', 'param_2']]
        );

        $this->handlerSession->add(['params', 1], 'change');
        $this->assertSame(
            $this->handlerSession->getAll(),
            ['id' => 1, 'title' => 'text3', 'params' => ['param_1', 'change']]
        );
        $this->handlerSession->add('params.0', 'change');
        $this->assertSame(
            $this->handlerSession->getAll(),
            ['id' => 1, 'title' => 'text3', 'params' => ['change', 'change']]
        );
    }


    public function testRemove()
    {
        $this->handlerSession->addMulti(['id' => 1, 'title' => 'text3', 'params' => ['param_1', 'param_2']]);
        $this->handlerSession->remove('params.1');
        $this->assertSame($this->handlerSession->getAll(), ['id' => 1, 'title' => 'text3', 'params' => ['param_1']]);
        $this->handlerSession->removeMulti(['id', 'params']);
        $this->assertSame($this->handlerSession->getAll(), ['title' => 'text3']);
    }

    public function testSetFlash()
    {
        $this->handlerSession->setFlash('flash_1', 'text');
        $this->assertSame($this->handlerSession->getFlash('flash_1'), 'text');
        $this->assertSame(
            $this->handlerSession->getAll(),
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
        $this->handlerSession->setFlash('flash_1', 'text');
        $this->handlerSession->setFlash('flash_2');
        $this->assertSame(
            $this->handlerSession->getAllFlashes(),
            array(
                'flash_1' => 'text',
                'flash_2' => true,
            )
        );
        $this->handlerSession->removeFlash('flash_2');
        $this->assertSame(
            $this->handlerSession->getAllFlashes(),
            array(
                'flash_1' => 'text',
            )
        );
        $this->handlerSession->removeAllFlashes();
        $this->assertSame($this->handlerSession->getAllFlashes(), []);
    }
}