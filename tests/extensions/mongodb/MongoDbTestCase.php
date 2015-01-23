<?php

namespace rockunit\extensions\mongodb;

use rock\base\Alias;
use rock\mongodb\Connection;
use rock\mongodb\MongoException;

class MongoDbTestCase extends \PHPUnit_Framework_TestCase
{
    public static $params;
    /**
     * @var array Mongo connection configuration.
     */
    protected $mongoDbConfig = [
        'dsn' => 'mongodb://localhost:27017',
        'defaultDatabaseName' => 'rocktest',
        'options' => [],
    ];
    /**
     * @var Connection Mongo connection instance.
     */
    protected $mongodb;

    protected function setUp()
    {
        parent::setUp();
        if (!extension_loaded('mongo')) {
            $this->markTestSkipped('mongo extension required.');
        }
        $config = self::getParam('mongodb');
        if (!empty($config)) {
            $this->mongoDbConfig = $config;
        }
    }

    protected function tearDown()
    {
        if ($this->mongodb) {
            $this->mongodb->close();
        }
    }


    /**
     * @param  boolean                 $reset whether to clean up the test database
     * @param  boolean                 $open  whether to open test database
     * @return \rock\mongodb\Connection
     */
    public function getConnection($reset = false, $open = true)
    {
        if (!$reset && $this->mongodb) {
            return $this->mongodb;
        }
        $connection = new Connection;
        $connection->dsn = $this->mongoDbConfig['dsn'];
        $connection->defaultDatabaseName = $this->mongoDbConfig['defaultDatabaseName'];
        if (isset($this->mongoDbConfig['options'])) {
            $connection->options = $this->mongoDbConfig['options'];
        }
        if ($open) {
            $connection->open();
        }
        $this->mongodb = $connection;

        return $connection;
    }

    /**
     * Drops the specified collection.
     * @param string $name collection name.
     */
    protected function dropCollection($name)
    {
        if ($this->mongodb) {
            try {
                $this->mongodb->getCollection($name)->drop();
            } catch (MongoException $e) {
                // shut down exception
            }
        }
    }

    /**
     * Drops the specified file collection.
     * @param string $name file collection name.
     */
    protected function dropFileCollection($name = 'fs')
    {
        if ($this->mongodb) {
            try {
                $this->mongodb->getFileCollection($name)->drop();
            } catch (MongoException $e) {
                // shut down exception
            }
        }
    }

    /**
     * Finds all records in collection.
     * @param  \rock\mongodb\Collection $collection
     * @param  array                   $condition
     * @param  array                   $fields
     * @return array                   rows
     */
    protected function findAll($collection, $condition = [], $fields = [])
    {
        $cursor = $collection->find($condition, $fields);
        $result = [];
        foreach ($cursor as $data) {
            $result[] = $data;
        }

        return $result;
    }

    /**
     * Returns the Mongo server version.
     * @return string Mongo server version.
     */
    protected function getServerVersion()
    {
        $connection = $this->getConnection();
        $buildInfo = $connection->getDatabase()->executeCommand(['buildinfo' => true]);

        return $buildInfo['version'];
    }

    /**
     * Returns a test configuration param from /data/config.php
     * @param  string $name    params name
     * @param  mixed  $default default value to use when param is not set.
     * @return mixed  the value of the configuration param
     */
    public static function getParam($name, $default = null)
    {
        if (static::$params === null) {
            static::$params = require(Alias::getAlias('@tests/data/config.php'));
        }

        return isset(static::$params[$name]) ? static::$params[$name] : $default;
    }
}
