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
        $this->fileManager = new FileManager(
            [
                'adapter' =>
                    function () {
                        return new Local(Alias::getAlias('@runtime/filesystem'));
                    },
                'cache' => function () {
                        $memcached = new \Memcached();
                        $memcached->addServer('localhost', 11211);

                        return new Memcached($memcached);
                    }
            ]
        );
        $this->fileManager->deleteAll();
    }
}
 