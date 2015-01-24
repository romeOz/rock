<?php

namespace rockunit\core\file;

use League\Flysystem\Adapter\Local;
use rock\base\Alias;
use rock\file\FileManager;
use League\Flysystem\Cache\Memcached;

/**
 * @group base
 * @group memcached
 */
class FileManagerWithMemcachedTest extends FileManagerTest
{
    protected function setUp()
    {
        $memcached = new \Memcached();
        $memcached->addServer('localhost', 11211);
        $config = [
            'adapter' => new Local(Alias::getAlias('@runtime/filesystem')),
            'cache' => new Memcached($memcached)
        ];
        $this->fileManager = new FileManager($config);
        $this->fileManager->deleteAll();
    }
}
 