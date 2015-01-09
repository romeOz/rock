<?php

namespace rockunit\core\file;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Cache\Adapter;
use rock\file\FileManager;
use rock\Rock;
use rockunit\common\CommonTestTrait;


/**
 * @group base
 */
class FileManagerTest extends \PHPUnit_Framework_TestCase
{
    use CommonTestTrait;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        static::clearRuntime();
    }

    /** @var  FileManager */
    protected $fileManager;

    protected function setUp()
    {
        parent::setUp(); 
        $this->fileManager = new FileManager([
             'adapter' =>
                 function () {
                     return new Local(Rock::getAlias('@runtime/filesystem'));
                 }
         ]);
        $this->fileManager->deleteAll();
    }


    public function testHasSuccess()
    {
        $this->assertTrue($this->fileManager->write('foo.tmp', 'foo'));
        $this->assertTrue($this->fileManager->write('test/bar.tmp', 'bar'));
        $this->assertTrue($this->fileManager->has('test/bar.tmp', FileManager::TYPE_FILE));
        $this->assertTrue($this->fileManager->has('test', FileManager::TYPE_DIR));
    }

    public function testHasFail()
    {
        $this->assertTrue($this->fileManager->write('foo.tmp', 'foo'));
        $this->assertTrue($this->fileManager->write('test/bar.tmp', 'bar'));
        $this->assertFalse($this->fileManager->has('test/bar.tmp', FileManager::TYPE_DIR));
        $this->assertFalse($this->fileManager->has('test', FileManager::TYPE_FILE));
    }

    public function testUpdate()
    {
        $this->assertTrue($this->fileManager->write('foo.tmp', 'foo'));
        $this->assertTrue($this->fileManager->update('foo.tmp', 'update'));
        $this->assertSame($this->fileManager->read('foo.tmp'), 'update');
        $this->assertFalse($this->fileManager->update('update.tmp', 'update'));
    }

    public function testWriteSuccess()
    {
        $this->assertTrue($this->fileManager->write('foo.tmp', 'foo'));
        $this->assertTrue($this->fileManager->has('foo.tmp'));
        $this->assertTrue($this->fileManager->delete('foo.tmp'));
        $this->assertFalse($this->fileManager->has('foo.tmp'));
        $this->assertTrue($this->fileManager->write('0', 'hh'));
        $this->assertTrue($this->fileManager->has('0'));
        $this->assertTrue($this->fileManager->delete('0'));
        $this->assertFalse($this->fileManager->has('0'));
    }

    public function testWriteFail()
    {
        $this->assertTrue($this->fileManager->write('foo.tmp', 'foo'));
        $this->assertFalse($this->fileManager->write('foo.tmp', 'foo'));
        $this->assertTrue($this->fileManager->delete('foo.tmp'));
    }

//    public function testWriteStream()
//    {
//        $this->fileManager->deleteAll();
//        $this->assertTrue($this->fileManager->write('test.tmp', 'foo'));
//        $this->assertTrue($this->fileManager->writeStream('baz.tmp', $this->fileManager->readStream('test.tmp')));
//        $this->assertTrue($this->fileManager->has('test.tmp'));
//        $this->assertTrue($this->fileManager->has('baz.tmp'));
//        $this->assertTrue($this->fileManager->writeStream('0', $this->fileManager->readStream('foo.tmp')));
//        $this->assertTrue($this->fileManager->has('foo.tmp'));
//        $this->assertTrue($this->fileManager->has('0'));
//
//        // repeat write fail
//        $this->assertFalse($this->fileManager->writeStream('0', $this->fileManager->readStream('foo.tmp')));
//        $this->fileManager->deleteAll();
//    }


    public function testPut()
    {
        $this->assertTrue($this->fileManager->put('foo.tmp', 'foo'));
        $this->assertSame($this->fileManager->read('foo.tmp'), 'foo');
        $this->assertTrue($this->fileManager->put('foo.tmp', 'test'));
        $this->assertSame($this->fileManager->read('foo.tmp'), 'test');
        $this->assertTrue($this->fileManager->delete('foo.tmp'));
    }


//    public function testUpdateStream()
//    {
//        $this->assertTrue($this->fileManager->write('foo.tmp', 'foo'));
//        $this->assertTrue($this->fileManager->write('bar.tmp', ''));
//        $this->assertTrue($this->fileManager->updateStream('bar.tmp', $this->fileManager->readStream('foo.tmp')));
//        $this->assertTrue($this->fileManager->has('foo.tmp'));
//        $this->assertTrue($this->fileManager->has('bar.tmp'));
//        $this->assertSame($this->fileManager->read('bar.tmp'), 'foo');
//        $this->assertTrue($this->fileManager->delete('bar.tmp'));
//        $this->assertTrue($this->fileManager->write('bar.tmp', '', FileManager::VISIBILITY_PRIVATE));
//        $this->assertFalse($this->fileManager->updateStream('baz.tmp', $this->fileManager->readStream('foo.tmp')));
//    }

    public function testDeleteSuccess()
    {
        $this->assertTrue($this->fileManager->write('foo.tmp', 'foo'));
        $this->assertTrue($this->fileManager->delete('~/^foo/'));
        $this->assertFalse($this->fileManager->delete('~/^foo/'));
    }

    public function testDeleteFail()
    {
        $this->assertTrue($this->fileManager->write('foo.tmp', 'foo'));
        $this->assertFalse($this->fileManager->delete('foo'));
        $this->assertFalse($this->fileManager->delete('~/foo$/'));
        $this->assertTrue($this->fileManager->delete('foo.tmp'));
    }

    public function testReadSuccess()
    {
        $this->assertTrue($this->fileManager->write('foo.tmp', 'foo'));
        $this->assertSame($this->fileManager->read('~/^foo/'), 'foo');
        $this->assertSame($this->fileManager->read('foo.tmp'), 'foo');
        $this->assertTrue($this->fileManager->delete('foo.tmp'));
    }

    public function testReadFail()
    {
        $this->assertTrue($this->fileManager->write('foo.tmp', 'foo'));
        $this->assertFalse($this->fileManager->read('~/foo$/'));
        $this->assertFalse($this->fileManager->read('foo'));
        $this->assertSame(
            $this->fileManager->getErrors(),
            array(
                'Unknown file: /foo$/.',
                'Unknown file: foo.',
            )
        );
        $this->assertTrue($this->fileManager->delete('foo.tmp'));
    }

    public function testReadAndDeleteSuccess()
    {
        $this->assertTrue($this->fileManager->write('foo.tmp', 'foo'));
        $this->assertSame($this->fileManager->readAndDelete('~/^foo/'), 'foo');
        $this->assertFalse($this->fileManager->read('foo.tmp'));
        $this->assertSame(
            [
                'Unknown file: foo.tmp.',
            ],
            $this->fileManager->getErrors()
        );
        $this->assertFalse($this->fileManager->delete('foo.tmp'));
    }

    public function testReadAndDeleteFail()
    {
        $this->assertTrue($this->fileManager->write('foo.tmp', 'foo'));
        $this->assertFalse($this->fileManager->readAndDelete('~/foo$/'));
        $this->assertFalse($this->fileManager->readAndDelete('foo'));
        $this->assertSame(
            [
                'Unknown file: /foo$/.',
                'Unknown file: foo.',
            ],
            $this->fileManager->getErrors()
        );
        $this->assertTrue($this->fileManager->delete('foo.tmp'));
    }

    public function testListContents()
    {
        $this->assertTrue($this->fileManager->write('foo.tmp', 'foo'));
        $this->assertTrue($this->fileManager->write('test/bar.tmp', 'bar'));

        $this->assertSame(count($this->fileManager->listContents()), 2);
        $this->assertSame(count($this->fileManager->listContents('test')), 1);
        $this->assertSame(count($this->fileManager->listContents('test/foo')), 0);

        $this->assertSame(count($this->fileManager->listContents('', true)), 3);
        $this->assertSame(count($this->fileManager->listContents('', true, FileManager::TYPE_DIR)), 1);
        $this->assertSame(count($this->fileManager->listContents('~/bar\.tmp$/', true, FileManager::TYPE_FILE)), 1);
        $this->assertSame(count($this->fileManager->listContents('~/bar\.tmp$/')), 0);
    }

    public function testListPaths()
    {
        $this->assertTrue($this->fileManager->write('foo.tmp', 'foo'));
        $this->assertTrue($this->fileManager->write('test/bar.tmp', 'bar'));

        $this->assertSame(static::sort($this->fileManager->listPaths()), static::sort(['foo.tmp','test']));
        $this->assertSame(static::sort($this->fileManager->listPaths('', true)), static::sort(['foo.tmp','test', 'test/bar.tmp']));
        $this->assertSame($this->fileManager->listPaths('', true, FileManager::TYPE_DIR), ['test']);
        $this->assertSame(static::sort($this->fileManager->listPaths('', true, FileManager::TYPE_FILE)), static::sort(['foo.tmp', 'test/bar.tmp']));
        $this->assertSame(count($this->fileManager->listPaths('test')), 1);
        $this->assertSame(count($this->fileManager->listPaths('test/foo')), 0);
        $this->assertSame(count($this->fileManager->listPaths('~/bar\.tmp$/', true, FileManager::TYPE_FILE)), 1);
        $this->assertSame(count($this->fileManager->listPaths('~/bar\.tmp$/')), 0);
    }

    public function testListWith()
    {
        $this->assertTrue($this->fileManager->write('foo.tmp', 'foo'));
        $this->assertTrue($this->fileManager->write('test/bar.tmp', 'bar'));

        $this->assertArrayHasKey('timestamp', $this->fileManager->listWith([FileManager::META_TIMESTAMP])[0]);
        $this->assertSame(count($this->fileManager->listWith([FileManager::META_TIMESTAMP])), 2);
        $this->assertSame(count($this->fileManager->listWith([FileManager::META_TIMESTAMP], 'test')), 1);
        $this->assertSame(count($this->fileManager->listWith([FileManager::META_TIMESTAMP],'test/foo')), 0);

        $this->assertSame(count($this->fileManager->listWith([FileManager::META_TIMESTAMP],'', true)), 3);
        $this->assertSame(count($this->fileManager->listWith([FileManager::META_TIMESTAMP],'', true, FileManager::TYPE_DIR)), 1);
        $this->assertSame(count($this->fileManager->listWith([FileManager::META_TIMESTAMP],'~/bar\.tmp$/', true, FileManager::TYPE_FILE)), 1);
        $this->assertSame(count($this->fileManager->listWith([FileManager::META_TIMESTAMP],'~/bar\.tmp$/')), 0);
    }

    public function testGetTimestamp()
    {
        $this->assertTrue($this->fileManager->write('foo.tmp', 'foo'));
        $this->assertTrue($this->fileManager->write('test/bar.tmp', 'bar'));
        $this->assertInternalType('int', $this->fileManager->getTimestamp('foo.tmp'));
        $this->assertSame(strlen($this->fileManager->getTimestamp('foo.tmp')), 10);
        $this->assertFalse($this->fileManager->getTimestamp('test/foo'));
        $this->assertInternalType('int', $this->fileManager->getTimestamp('~/bar\.tmp$/'));
        $this->assertSame(strlen($this->fileManager->getTimestamp('~/bar\.tmp$/')), 10);
        $this->assertFalse($this->fileManager->getTimestamp('~/baz\.tmp$/'));
    }

    public function testGetMimetype()
    {
        $this->assertTrue($this->fileManager->write('foo.tmp', 'foo'));
        $this->assertTrue($this->fileManager->write('test/bar.tmp', 'bar'));
        $this->assertSame($this->fileManager->getMimetype('foo.tmp'), 'text/plain');
        $this->assertFalse($this->fileManager->getMimetype('test/foo'));
        $this->assertSame($this->fileManager->getMimetype('~/bar\.tmp$/'), 'text/plain');
        $this->assertFalse($this->fileManager->getMimetype('~/baz\.tmp$/'));
    }

    public function testGetWithMetadata()
    {
        $this->fileManager->deleteAll();
        $this->assertTrue($this->fileManager->write('foo.tmp', 'foo'));
        $this->assertTrue($this->fileManager->write('test/bar.tmp', 'bar'));
        $this->assertSame($this->fileManager->getWithMetadata('foo.tmp', [FileManager::META_MIMETYPE])["mimetype"], 'text/plain');
        $this->assertFalse($this->fileManager->getWithMetadata('test/foo', [FileManager::META_MIMETYPE]));
        $this->assertSame($this->fileManager->getWithMetadata('~/bar\.tmp$/', [FileManager::META_MIMETYPE])["mimetype"], 'text/plain');
        $this->assertFalse($this->fileManager->getWithMetadata('~/baz\.tmp$/', [FileManager::META_MIMETYPE]));
    }

    public function testGetMetadata()
    {
        $this->assertTrue($this->fileManager->write('foo.tmp', 'foo'));
        $this->assertTrue($this->fileManager->write('test/bar.tmp', 'bar'));
        $this->assertSame($this->fileManager->getMetadata('foo.tmp')["type"], 'file');
        $this->assertFalse($this->fileManager->getMetadata('test/foo'));
        $this->assertSame($this->fileManager->getMetadata('~/bar\.tmp$/')["type"], 'file');
        $this->assertFalse($this->fileManager->getMetadata('~/baz\.tmp$/'));
    }

    public function testGetSize()
    {
        $this->assertTrue($this->fileManager->write('foo.tmp', 'foo'));
        $this->assertTrue($this->fileManager->write('test/bar.tmp', 'bar'));
        $this->assertSame($this->fileManager->getSize('foo.tmp'), 3);
        $this->assertFalse($this->fileManager->getSize('test/foo'));
        $this->assertSame($this->fileManager->getSize('~/bar\.tmp$/'), 3);
        $this->assertFalse($this->fileManager->getSize('~/baz\.tmp$/'));
    }

    public function testCreateDir()
    {
        $this->assertTrue($this->fileManager->createDir('test'));
        $this->assertTrue($this->fileManager->has('test'));
        $this->assertTrue($this->fileManager->createDir('0'));
        $this->assertTrue($this->fileManager->has('0'));
        $this->assertTrue($this->fileManager->deleteDir('0'));
        $this->assertFalse($this->fileManager->has('0'));
    }

    public function testVisibility()
    {
        $this->assertTrue($this->fileManager->write('foo.tmp', 'foo'));
        $this->assertSame($this->fileManager->getVisibility('foo.tmp'), FileManager::VISIBILITY_PUBLIC);
        $this->assertFalse($this->fileManager->getVisibility('baz.tmp'));
        $this->fileManager->deleteAll();
        $this->assertTrue($this->fileManager->write('foo.tmp', 'foo', ['visibility' => FileManager::VISIBILITY_PRIVATE]));
        $this->assertSame($this->fileManager->getVisibility('foo.tmp'), FileManager::VISIBILITY_PRIVATE);
        $this->assertSame($this->fileManager->getVisibility('~/foo\.tmp$/'), FileManager::VISIBILITY_PRIVATE);
        $this->assertFalse($this->fileManager->getVisibility('~/baz\.tmp$/'));
    }

    public function testRenameSuccess()
    {
        $this->assertTrue($this->fileManager->createDir('test'));
        $this->assertTrue($this->fileManager->rename('test', 'test_1'));
        $this->assertFalse($this->fileManager->has('test'));
        $this->assertTrue($this->fileManager->has('test_1'));
    }

    public function testRenameFail()
    {
        $this->assertFalse($this->fileManager->rename('test', 'test_1'));
    }

    public function testRenameByMask()
    {
        $this->assertTrue($this->fileManager->write('test', ''));
        $this->assertTrue($this->fileManager->renameByMask('test', 'test_{num}', ['num' => 2]));
        $this->assertTrue($this->fileManager->has('test_2'));
        $this->assertFalse($this->fileManager->renameByMask('test', 'test_{num}', ['num' => 2]));
    }

    public function testCopy()
    {
        $this->assertTrue($this->fileManager->write('test/foo.tmp', 'foo'));
        $this->assertTrue($this->fileManager->createDir('test_1'));
        $this->assertTrue($this->fileManager->copy('test/foo.tmp', 'test_1/foo.tmp'));
        $this->assertTrue($this->fileManager->has('test/foo.tmp'));
        $this->assertTrue($this->fileManager->has('test_1/foo.tmp'));
        $this->assertFalse($this->fileManager->copy('test/foo.tmp', 'test_1/'));
    }

    protected static function getFileManagerWithLocalCache()
    {
        return                 new FileManager(
            [
                'adapter' =>
                    function () {
                        return new Local(Rock::getAlias('@runtime/filesystem'));
                    },
                'cache' => function () {
                        $local = new Local(Rock::getAlias('@runtime'));
                        $cache = new Adapter($local, 'filesystem.tmp');

                        return $cache;
                    }
            ]
        );
    }

    public static function tearDownAfterClass()
    {
        $memcached = new \Memcached();
        $memcached->addServer('localhost', 11211);
        $memcached->flush();
        static::getFileManagerWithLocalCache()->deleteAll();
        static::getFileManagerWithLocalCache()->flushCache();
        static::clearRuntime();
    }
}
 