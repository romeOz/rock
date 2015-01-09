<?php

namespace rockunit\core\session;


use rockunit\core\session\mocks\DbSessionMock;

trait CommonSessionTrait
{
    /** @var  DbSessionMock */
    public $handlerSession;
    /**
     * @dataProvider providerGet
     */
    public function testGet($expected, $actual, $keys, $default = null)
    {
        $this->handlerSession->addMulti($expected);
        $this->assertSame($actual, $this->handlerSession->get($keys, $default));
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
            ['title' => 'text3', 'params.1' => 'param_2'],
            $this->handlerSession->getMulti(['title', ['params', 1]])
        );
    }

    public function testGetAll()
    {
        $this->handlerSession->addMulti(['id' => 1, 'title' => 'text3', 'params' => ['param_1', 'param_2']]);
        $this->assertSame(
            ['id' => 1, 'title' => 'text3', 'params' => ['param_1', 'param_2']],
            $this->handlerSession->getAll()
        );
        $this->assertSame(['title' => 'text3'], $this->handlerSession->getAll(['title', 'params'], ['params']));
    }

    public function testGetIterator()
    {
        $this->handlerSession->addMulti(['id' => 1, 'title' => 'text3', 'params' => ['param_1', 'param_2']]);
        $this->assertSame(1, $this->handlerSession->getIterator([], ['title'])->current());
        $this->assertSame(
            ['id' => 1, 'params' => ['param_1', 'param_2']],
            $this->handlerSession->getIterator([], ['title'])->getArrayCopy()
        );
    }

    public function testCount()
    {
        $this->handlerSession->add('foo', 'test');
        $this->assertSame(1, $this->handlerSession->getCount());
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
            ['id' => 1, 'title' => 'text3', 'params' => ['param_1', 'param_2']],
            $this->handlerSession->getAll()
        );

        $this->handlerSession->add(['params', 1], 'change');
        $this->assertSame(
            ['id' => 1, 'title' => 'text3', 'params' => ['param_1', 'change']],
            $this->handlerSession->getAll()
        );
        $this->handlerSession->add('params.0', 'change');
        $this->assertSame(
            ['id' => 1, 'title' => 'text3', 'params' => ['change', 'change']],
            $this->handlerSession->getAll()
        );
    }

    public function testRemove()
    {
        $this->handlerSession->addMulti(['id' => 1, 'title' => 'text3', 'params' => ['param_1', 'param_2']]);
        $this->handlerSession->remove('params.1');
        $this->assertSame(['id' => 1, 'title' => 'text3', 'params' => ['param_1']], $this->handlerSession->getAll());
        $this->handlerSession->removeMulti(['id', 'params']);
        $this->assertSame(['title' => 'text3'], $this->handlerSession->getAll());
    }

    public function testSetFlash()
    {
        $this->handlerSession->setFlash('flash_1', 'text');
        $this->assertSame($this->handlerSession->getFlash('flash_1'), 'text');
        $this->assertSame(
            [
                'flash_1' => 'text',
                '__flash' =>
                    [
                        'flash_1' => 0,
                    ],
            ],
            $this->handlerSession->getAll()
        );
    }

    public function testGetAllFlashes()
    {
        $this->handlerSession->setFlash('flash_1', 'text');
        $this->handlerSession->setFlash('flash_2');
        $this->assertSame(
            [
                'flash_1' => 'text',
                'flash_2' => true,
            ],
            $this->handlerSession->getAllFlashes()
        );
        $this->handlerSession->removeFlash('flash_2');
        $this->assertSame(
            [
                'flash_1' => 'text',
            ],
            $this->handlerSession->getAllFlashes()
        );
        $this->handlerSession->removeAllFlashes();
        $this->assertSame([], $this->handlerSession->getAllFlashes());
    }

    public function testExpire()
    {
        $this->markTestSkipped(
            __METHOD__ . ' skipped.'
        );
    }

    public function testGC()
    {
        /** @var $this \PHPUnit_Framework_TestCase */

        $this->markTestSkipped(
            __METHOD__ . ' skipped.'
        );
    }
}