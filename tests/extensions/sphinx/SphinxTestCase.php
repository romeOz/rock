<?php

namespace rockunit\extensions\sphinx;

use rock\db\Migration;
use rock\Rock;
use rock\sphinx\Connection;

/**
 * Base class for the Sphinx test cases.
 */
class SphinxTestCase extends \PHPUnit_Framework_TestCase
{
    public static $params;
    /**
     * @var array Sphinx connection configuration.
     */
    protected $sphinxConfig = [
        'dsn' => 'mysql:host=127.0.0.1;port=9306;',
        'username' => '',
        'password' => '',
    ];
    /**
     * @var Connection Sphinx connection instance.
     */
    protected $sphinx;
    /**
     * @var array Database connection configuration.
     */
    protected $dbConfig = [
        'dsn' => 'mysql:host=127.0.0.1;',
        'username' => '',
        'password' => '',
    ];
    /**
     * @var \rock\db\Connection database connection instance.
     */
    protected $connection;

//    public static function setUpBeforeClass()
//    {
//        static::loadClassMap();
//    }

    protected function setUp()
    {
        parent::setUp();
        $this->up();
        //$this->mockApplication();
        //static::loadClassMap();
    }

    public function up()
    {
        if (!extension_loaded('pdo') || !extension_loaded('pdo_mysql')) {
            $this->markTestSkipped('pdo and pdo_mysql extension are required.');
        }
        $config = self::getParam('sphinx');
        if (!empty($config)) {
            $this->sphinxConfig = $config['sphinx'];
            $this->dbConfig = $config['db'];
        }
    }

    protected function tearDown()
    {
        if ($this->sphinx) {
            $this->sphinx->close();
        }
        //$this->destroyApplication();
    }

//    /**
//     * Adds sphinx extension files to [[Rock::$classPath]],
//     * avoiding the necessity of usage Composer autoloader.
//     */
//    protected static function loadClassMap()
//    {
//        $baseNameSpace = 'rock/sphinx';
//        $basePath = realpath(__DIR__. '/../../../../extensions/sphinx');
//        $files = FileHelper::findFiles($basePath);
//        foreach ($files as $file) {
//            $classRelativePath = str_replace($basePath, '', $file);
//            $classFullName = str_replace(['/', '.php'], ['\\', ''], $baseNameSpace . $classRelativePath);
//            Yii::$classMap[$classFullName] = $file;
//        }
//    }

    /**
     * @param  boolean                $reset whether to clean up the test database
     * @param  boolean                $open  whether to open test database
     * @return \rock\sphinx\Connection
     */
    public function getConnection($reset = false, $open = true)
    {
        if (!$reset && $this->sphinx) {
            return $this->sphinx;
        }
        $connection = new Connection;
        $connection->dsn = $this->sphinxConfig['dsn'];
        if (isset($this->sphinxConfig['username'])) {
            $connection->username = $this->sphinxConfig['username'];
            $connection->password = $this->sphinxConfig['password'];
        }
        if (isset($this->sphinxConfig['attributes'])) {
            $connection->attributes = $this->sphinxConfig['attributes'];
        }
        if ($open) {
            $connection->open();
        }
        $this->sphinx = $connection;

        return $connection;
    }

    /**
     * Truncates the runtime index.
     * @param string $indexName index name.
     */
    protected function truncateRuntimeIndex($indexName)
    {
        if ($this->sphinx) {
            $this->sphinx->createCommand('TRUNCATE RTINDEX ' . $indexName)->execute();
        }
    }

    /**
     * @param  boolean            $reset whether to clean up the test database
     * @param  boolean            $open  whether to open and populate test database
     * @return \rock\db\Connection
     */
    public function getDbConnection($reset = true, $open = true)
    {
        if (!$reset && $this->connection) {
            return $this->connection;
        }

        $connection = new \rock\db\Connection;
        $connection->dsn = $this->dbConfig['dsn'];
        if (isset($this->dbConfig['username'])) {
            $connection->username = $this->dbConfig['username'];
            $connection->password = $this->dbConfig['password'];
        }
        if (isset($this->dbConfig['attributes'])) {
            $connection->attributes = $this->dbConfig['attributes'];
        }
        if ($open) {
            $connection->open();
            if (!empty($this->dbConfig['fixture'])) {
                $lines = explode(';', file_get_contents($this->dbConfig['fixture']));
                foreach ($lines as $line) {
                    if (trim($line) !== '') {
                        $connection->pdo->exec($line);
                    }
                }
            }

            if (isset($this->dbConfig['migrations'])) {
                /** @var Migration $migration */
                foreach ($this->dbConfig['migrations'] as $migration) {
                    if (is_string($migration)) {
                        $migration = new $migration;
                    }
                    $migration->connection = $connection;
                    $migration->enableNote = false;
                    $migration->up();
                }
            }
        }
        $this->connection = $connection;

        return $connection;
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
            static::$params = require(Rock::getAlias('@tests/data/config.php'));
        }

        return isset(static::$params[$name]) ? static::$params[$name] : $default;
    }
}
