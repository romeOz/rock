<?php

namespace rockunit\core\file;

use League\Flysystem\Adapter\Local;
use rock\Rock;
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
                        return new Local(Rock::getAlias('@runtime/filesystem'));
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
 