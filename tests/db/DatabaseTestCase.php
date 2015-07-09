<?php

namespace rockunit\db;


use PHPUnit_Framework_TestCase;
use rock\base\Alias;
use rock\db\Connection;
use rock\db\Migration;
use rock\di\Container;
use rock\helpers\ArrayHelper;
use rock\helpers\Instance;

class DatabaseTestCase extends \PHPUnit_Framework_TestCase
{
    public static $params;
    protected $database;
    protected $driverName = 'mysql';
    /**
     * @var Connection|string
     */
    protected $connection = 'db';

    protected function setUp()
    {
        parent::setUp();
        $databases = static::getParam('databases');
        $this->database = $databases[$this->driverName];
        $pdo_database = 'pdo_'.$this->driverName;

        if (!extension_loaded('pdo') || !extension_loaded($pdo_database)) {
            $this->markTestSkipped('pdo and '.$pdo_database.' extension are required.');
        }
        //$this->mockApplication();

        //throw new \Exception('PDO not exists.');
    }

    protected function tearDown()
    {
        if ($this->connection instanceof Connection) {
            $this->connection->close();
        }
        //$this->destroyApplication();
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
            static::$params = require(Alias::getAlias('@rockunit/data/config.php'));
        }

        return isset(static::$params[$name]) ? static::$params[$name] : $default;
    }

    /**
     * @param  boolean            $reset whether to clean up the test database
     * @param  boolean            $open  whether to open and populate test database
     * @return Connection
     */
    public function getConnection($reset = true, $open = true)
    {
        if (!$reset && $this->connection instanceof Connection) {
            return $this->connection;
        }
        $config = $this->database;
        $fixture = isset($config['fixture']) ? $config['fixture'] : null;
        $migrations = isset($config['migrations']) ? $config['migrations'] : [];
        $config = ArrayHelper::intersectByKeys($config, ['dsn', 'username', 'password', 'attributes']);

        try {
            $this->connection = $this->prepareDatabase($config, $fixture, $open, $migrations);
        } catch (\Exception $e) {
            $this->markTestSkipped("Something wrong when preparing database: " . $e->getMessage());
        }
        return $this->connection;
    }

    public function prepareDatabase($config, $fixture, $open = true, array $migrations = [])
    {
        if (!isset($config['class'])) {
            $config['class'] = Connection::className();
        }
        if (is_string($this->connection)) {
            Container::register($this->connection, $config);
        }

        /** @var Connection $connection */
        $connection = Container::load($config);
        if (!$open) {
            return $connection;
        }

        $connection->open();
        if ($fixture !== null) {
            $lines = explode(';', file_get_contents($fixture));
            foreach ($lines as $line) {
                if (trim($line) !== '') {
                    $connection->pdo->exec($line);
                }
            }
        }

        $this->applyMigrations($connection, $migrations);

        return $connection;
    }

    protected function applyMigrations(Connection $connection, array $migrations)
    {
        foreach ($migrations as $config) {
            $config = array_merge(['connection' => $connection, 'enableVerbose' => false], $config);
            /** @var Migration $migration */
            $migration = Instance::ensure($config);
            $migration->enableVerbose = false;
            $migration->up();
        }
    }

    /**
     * adjust dbms specific escaping
     * @param $sql
     * @return mixed
     */
    protected function replaceQuotes($sql)
    {
        if (!in_array($this->driverName, ['mssql', 'mysql', 'sqlite'])) {
            return str_replace('`', '"', $sql);
        }
        return $sql;
    }
} 