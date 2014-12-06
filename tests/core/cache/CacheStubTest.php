<?php

namespace rockunit\core\cache;


use rock\cache\CacheStub;

class CacheStubTest extends \PHPUnit_Framework_TestCase
{
    /** @var  CacheStub */
    protected $storage;

    protected function setUp()
    {
        $this->storage = new CacheStub();
    }

    /**
     * @expectedException \rock\cache\CacheException
     */
    public function testGetStorage()
    {
        $this->storage->getStorage();
    }

    public function testGet()
    {
        $this->assertFalse($this->storage->get('foo'));
    }

    public function testGetMulti()
    {
        $this->assertSame([], $this->storage->getMulti(['foo', 'bar']));
    }

    public function testSet()
    {
        $this->assertFalse($this->storage->set('foo'));
    }

    public function testAdd()
    {
        $this->assertFalse($this->storage->add('foo'));
    }

    public function testExists()
    {
        $this->assertFalse($this->storage->exists('foo'));
    }

    public function testTouch()
    {
        $this->assertFalse($this->storage->touch('foo'));
    }

    public function testIncrement()
    {
        $this->assertFalse($this->storage->increment('foo'));
    }

    public function testDecrement()
    {
        $this->assertFalse($this->storage->decrement('foo'));
    }

    public function testRemove()
    {
        $this->assertFalse($this->storage->remove('foo'));
    }

    public function testGetTag()
    {
        $this->assertFalse($this->storage->getTag('foo'));
    }

    public function testGetMultiTags()
    {
        $this->assertSame([], $this->storage->getMultiTags(['foo', 'bar']));
    }

    public function testExistsTag()
    {
        $this->assertFalse($this->storage->existsTag('foo'));
    }

    public function testRemoveTag()
    {
        $this->assertFalse($this->storage->removeTag('foo'));
    }

    public function testGetAllKeys()
    {
        $this->assertSame([], $this->storage->getAllKeys());
    }

    public function testGetAll()
    {
        $this->assertSame([], $this->storage->getAll());
    }

    public function testFlush()
    {
        $this->assertFalse($this->storage->flush());
    }

    public function testStatus()
    {
        $this->assertNull($this->storage->status());
    }
}
 