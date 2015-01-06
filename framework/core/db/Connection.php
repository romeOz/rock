<?php
namespace rock\db;

use PDO;
use rock\base\ComponentsTrait;
use rock\base\ObjectInterface;
use rock\cache\CacheInterface;
use rock\di\Container;
use rock\Rock;

/**
 * Connection represents a connection to a database via [PDO](http://www.php.net/manual/en/book.pdo.php).
 *
 * Connection works together with {@see \rock\db\Command}, {@see \rock\db\DataReader} and {@see \rock\db\Transaction}
 * to provide data access to various DBMS in a common set of APIs. They are a thin wrapper
 * of the [PDO PHP extension](http://www.php.net/manual/en/book.pdo.php).
 *
 * Connection supports database replication and read-write splitting. In particular, a Connection component
 * can be configured with multiple {@see \rock\db\Connection::$masters} and {@see \rock\db\Connection::$slaves}. It will do load balancing and failover by choosing
 * appropriate servers. It will also automatically direct read operations to the slaves and write operations to
 * the masters.
 *
 * To establish a DB connection, set {@see \rock\db\Connection::$dsn}, {@see \rock\db\Connection::$username} and {@see \rock\db\Connection::$password}, and then
 * call {@see \rock\db\Connection::open()} to be true.
 *
 * The following example shows how to create a Connection instance and establish
 * the DB connection:
 *
 * ```php
 * $connection = new \rock\db\Connection([
 *     'dsn' => $dsn,
 *     'username' => $username,
 *     'password' => $password,
 * ]);
 * $connection->open();
 * ```
 *
 * After the DB connection is established, one can execute SQL statements like the following:
 *
 * ```php
 * $command = $connection->createCommand('SELECT * FROM post');
 * $posts = $command->queryAll();
 * $command = $connection->createCommand('UPDATE post SET status=1');
 * $command->execute();
 * ```
 *
 * One can also do prepared SQL execution and bind parameters to the prepared SQL.
 * When the parameters are coming from user input, you should use this approach
 * to prevent SQL injection attacks. The following is an example:
 *
 * ```php
 * $command = $connection->createCommand('SELECT * FROM post WHERE id=:id');
 * $command->bindValue(':id', $_GET['id']);
 * $post = $command->query();
 * ```
 *
 * For more information about how to perform various DB queries, please refer to {@see \rock\db\Command}.
 *
 * If the underlying DBMS supports transactions, you can perform transactional SQL queries
 * like the following:
 *
 * ```php
 * $transaction = $connection->beginTransaction();
 * try {
 *     $connection->createCommand($sql1)->execute();
 *     $connection->createCommand($sql2)->execute();
 *     // ... executing other SQL statements ...
 *     $transaction->commit();
 * } catch (Exception $e) {
 *     $transaction->rollBack();
 * }
 * ```
 *
 * You also can use shortcut for the above like the following:
 *
 * ```php
 * $connection->transaction(function() {
 *     $order = new Order($customer);
 *     $order->save();
 *     $order->addItems($items);
 * });
 * ```
 *
 * If needed you can pass transaction isolation level as a second parameter:
 *
 * ```php
 * $connection->transaction(function(Connection $connection) {
 *     // $connection->...
 * }, Transaction::READ_UNCOMMITTED);
 * ```
 *
 * Connection is often used as an application component and configured in the application
 * configuration like the following:
 *
 * ```php
 * 'components' => [
 *     'db' => [
 *         'class' => '\rock\db\Connection',
 *         'dsn' => 'mysql:host=127.0.0.1;dbname=demo',
 *         'username' => 'root',
 *         'password' => '',
 *         'charset' => 'utf8',
 *     ],
 * ],
 * ```
 *
 * @property string $driverName Name of the DB driver.
 * @property boolean $isActive Whether the DB connection is established. This property is read-only.
 * @property string $lastInsertID The row ID of the last row inserted, or the last value retrieved from the
 * sequence object. This property is read-only.
 * @property PDO $masterPdo The PDO instance for the currently active master connection. This property is
 * read-only.
 * @property QueryBuilder $queryBuilder The query builder for the current DB connection. This property is
 * read-only.
 * @property Schema $schema The schema information for the database opened by this connection. This property
 * is read-only.
 * @property Connection $slave The currently active slave connection. Null is returned if there is slave
 * available and `$fallbackToMaster` is false. This property is read-only.
 * @property PDO $slavePdo The PDO instance for the currently active slave connection. Null is returned if no
 * slave connection is available and `$fallbackToMaster` is false. This property is read-only.
 * @property Transaction $transaction The currently active transaction. Null if no active transaction. This
 * property is read-only.
 */
class Connection implements ObjectInterface
{
    use ComponentsTrait;

    /**
     * @event Event an event that is triggered after a DB connection is established
     */
    const EVENT_AFTER_OPEN = 'afterOpen';
    /**
     * @event Event an event that is triggered right before a top-level transaction is started
     */
    const EVENT_BEGIN_TRANSACTION = 'beginTransaction';
    /**
     * @event Event an event that is triggered right after a top-level transaction is committed
     */
    const EVENT_COMMIT_TRANSACTION = 'commitTransaction';
    /**
     * @event Event an event that is triggered right after a top-level transaction is rolled back
     */
    const EVENT_ROLLBACK_TRANSACTION = 'rollbackTransaction';

    /**
     * @var string the Data Source Name, or DSN, contains the information required to connect to the database.
     * Please refer to the [PHP manual](http://www.php.net/manual/en/function.PDO-construct.php) on
     * the format of the DSN string.
     * @see charset
     */
    public $dsn;
    /**
     * @var string the username for establishing DB connection. Defaults to `null` meaning no username to use.
     */
    public $username;
    /**
     * @var string the password for establishing DB connection. Defaults to `null` meaning no password to use.
     */
    public $password;
    /**
     * @var array PDO attributes (name => value) that should be set when calling {@see \rock\db\Connection::open()}
     * to establish a DB connection. Please refer to the
     * [PHP manual](http://www.php.net/manual/en/function.PDO-setAttribute.php) for
     * details about available attributes.
     */
    public $attributes;
    /**
     * @var PDO the PHP PDO instance associated with this DB connection.
     * This property is mainly managed by {@see \rock\db\Connection::open()} and {@see \rock\db\Connection::close()} methods.
     * When a DB connection is active, this property will represent a PDO instance;
     * otherwise, it will be null.
     */
    public $pdo;
    /**
     * @var boolean whether to enable schema caching.
     * Note that in order to enable truly schema caching, a valid cache component as specified
     * by {@see \rock\db\Connection::$schemaCache} must be enabled and {@see \rock\db\Connection::$enableSchemaCache} must be set true.
     * @see schemaCacheTags
     * @see schemaCacheExclude
     * @see schemaCache
     */
    public $enableSchemaCache = false;
    /**
     * @var integer number of seconds that table metadata can remain valid in cache.
     * Use 0 to indicate that the cached data will never expire.
     * @see enableQueryCache
     */
    public $schemaCacheExpire = 0;
    /**
     * @var string[] the dependency that will be used when saving query results into cache.
     * Defaults to null, meaning no dependency.
     * @see enableSchemaCache
     */
    public $schemaCacheTags = [];
    /**
     * @var array list of tables whose metadata should NOT be cached. Defaults to empty array.
     * The table names may contain schema prefix, if any. Do not quote the table names.
     * @see enableSchemaCache
     */
    public $schemaCacheExclude = [];
    /**
     * @var \rock\cache\CacheInterface|string the cache object or the ID of the cache application component that
     * is used to cache the table metadata.
     * @see enableSchemaCache
     */
    public $schemaCache = 'cache';
    /**
     * @var boolean whether to enable query caching.
     * Note that in order to enable query caching, a valid cache component as specified
     * by {@see \rock\db\Connection::$queryCache} must be enabled and {@see \rock\db\Connection::$enableQueryCache} must be set true.
     *
     * Methods {@see \rock\db\QueryInterface::beginCache()} and {@see \rock\db\QueryInterface::endCache()} can be used as shortcuts to turn on
     * and off query caching on the fly.
     * @see queryCacheExpire
     * @see queryCache
     * @see queryCacheTags
     * @see beginCache()
     * @see endCache()
     */
    public $enableQueryCache = false;
    /**
     * @var integer number of seconds that query results can remain valid in cache.
     * Defaults to 0, meaning 0 seconds, or one hour.
     * Use 0 to indicate that the cached data will never expire.
     * @see enableQueryCache
     */
    public $queryCacheExpire = 0;
    /**
     * @var string[] the dependency that will be used when saving query results into cache.
     * Defaults to null, meaning no dependency.
     * @see enableQueryCache
     */
    public $queryCacheTags = [];
    /**
     * @var \rock\cache\CacheInterface|string the cache object or the ID of the cache application component
     * that is used for query caching.
     * @see enableQueryCache
     */
    public $queryCache = 'cache';
    /**
     * @var string the charset used for database connection. The property is only used
     * for MySQL, PostgreSQL and CUBRID databases. Defaults to null, meaning using default charset
     * as specified by the database.
     *
     * Note that if you're using GBK or BIG5 then it's highly recommended to
     * specify charset via DSN like 'mysql:dbname=mydatabase;host=127.0.0.1;charset=GBK;'.
     */
    public $charset;
    /**
     * @var boolean whether to turn on prepare emulation. Defaults to false, meaning PDO
     * will use the native prepare support if available. For some databases (such as MySQL),
     * this may need to be set true so that PDO can emulate the prepare support to bypass
     * the buggy native prepare support.
     * The default value is null, which means the PDO ATTR_EMULATE_PREPARES value will not be changed.
     */
    public $emulatePrepare;
    /**
     * @var string the common prefix or suffix for table names. If a table name is given
     * as `{{%TableName}}`, then the percentage character `%` will be replaced with this
     * property value. For example, `{{%post}}` becomes `{{tbl_post}}`.
     */
    public $tablePrefix = '';
    /**
     * `table`.`field` as `table__field`
     *
     * @var string
     */
    public $aliasSeparator = '__';

    public $typeCast = true;
    
    /**
     * @var array mapping between PDO driver names and {@see \rock\db\Schema} classes.
     * The keys of the array are PDO driver names while the values the corresponding
     * schema class name or configuration. Please refer to {@see \rock\Rock::factory()} for
     * details on how to specify a configuration.
     *
     * This property is mainly used by {@see \rock\db\Connection::getSchema()} when fetching the database schema information.
     * You normally do not need to set this property unless you want to use your own
     * {@see \rock\db\Schema} class to support DBMS that is not supported by Rock.
     */
    public $schemaMap = [
        'pgsql'   => 'rock\db\pgsql\Schema', // PostgreSQL
        'mysqli'  => 'rock\db\mysql\Schema', // MySQL
        'mysql'   => 'rock\db\mysql\Schema', // MySQL
        'sqlite'  => 'rock\db\sqlite\Schema', // sqlite 3
        'sqlite2' => 'rock\db\sqlite\Schema', // sqlite 2
        'sqlsrv'  => 'rock\db\mssql\Schema', // newer MSSQL driver on MS Windows hosts
        'oci'     => 'rock\db\oci\Schema', // Oracle driver
        'mssql'   => 'rock\db\mssql\Schema', // older MSSQL driver on MS Windows hosts
        'dblib'   => 'rock\db\mssql\Schema', // dblib drivers on GNU/Linux (and maybe other OSes) hosts
        'cubrid'  => 'rock\db\cubrid\Schema', // CUBRID
    ];
    /**
     * @var string Custom PDO wrapper class. If not set, it will use "PDO" or {@see \rock\db\mssql\PDO} when MSSQL is used.
     */
    public $pdoClass;
    /**
     * @var boolean whether to enable [savepoint](http://en.wikipedia.org/wiki/Savepoint).
     * Note that if the underlying DBMS does not support savepoint, setting this property to be true will have no effect.
     */
    public $enableSavepoint = true;
    /**
     * @var \rock\cache\CacheInterface|string the cache object or the ID of the cache application component that is used to store
     * the health status of the DB servers specified in {@see \rock\db\Connection::$masters} and {@see \rock\db\Connection::$slaves}.
     * This is used only when read/write splitting is enabled or {@see \rock\db\Connection::$masters} is not empty.
     */
    public $serverStatusCache = 'cache';
    /**
     * @var integer the retry interval in seconds for dead servers listed in {@see \rock\db\Connection::$masters} and {@see \rock\db\Connection::$slaves}.
     * This is used together with {@see \rock\db\Connection::$serverStatusCache}.
     */
    public $serverRetryInterval = 600;
    /**
     * @var boolean whether to enable read/write splitting by using {@see \rock\db\Connection::$slaves} to read data.
     * Note that if {@see \rock\db\Connection::$slaves} is empty, read/write splitting will NOT be enabled no matter what value this property takes.
     */
    public $enableSlaves = true;
    /**
     * @var array list of slave connection configurations. Each configuration is used to create a slave DB connection.
     * When {@see \rock\db\Connection::$enableSlaves} is true, one of these configurations will be chosen and used to create a DB connection
     * for performing read queries only.
     * @see enableSlaves
     * @see slaveConfig
     */
    public $slaves = [];
    /**
     * @var array the configuration that should be merged with every slave configuration listed in {@see \rock\db\Connection::$slaves}.
     * For example,
     *
     * ```php
     * [
     *     'username' => 'slave',
     *     'password' => 'slave',
     *     'attributes' => [
     *         // use a smaller connection timeout
     *         PDO::ATTR_TIMEOUT => 10,
     *     ],
     * ]
     * ```
     */
    public $slaveConfig = [];
    /**
     * @var array list of master connection configurations. Each configuration is used to create a master DB connection.
     * When {@see \rock\db\Connection::open()} is called, one of these configurations will be chosen and used to create a DB connection
     * which will be used by this object.
     * Note that when this property is not empty, the connection setting (e.g. "dsn", "username") of this object will
     * be ignored.
     * @see masterConfig
     */
    public $masters = [];
    /**
     * @var array the configuration that should be merged with every master configuration listed in {@see \rock\db\Connection::$masters}.
     * For example,
     *
     * ```php
     * [
     *     'username' => 'master',
     *     'password' => 'master',
     *     'attributes' => [
     *         // use a smaller connection timeout
     *         PDO::ATTR_TIMEOUT => 10,
     *     ],
     * ]
     * ```
     */
    public $masterConfig = [];

    /**
     * @var Transaction the currently active transaction
     */
    private $_transaction;
    /**
     * @var Schema the database schema
     */
    private $_schema;
    /**
     * @var string driver name
     */
    private $_driverName;
    /**
     * @var Connection the currently active slave connection
     */
    private $_slave = false;


    /**
     * Returns a value indicating whether the DB connection is established.
     * @return boolean whether the DB connection is established
     */
    public function getIsActive()
    {
        return $this->pdo !== null;
    }

    /**
     * Establishes a DB connection.
     * It does nothing if a DB connection has already been established.
     * @throws Exception if connection fails
     */
    public function open()
    {
        if ($this->pdo !== null) {
            return;
        }

        if (!empty($this->masters)) {
            $connection = $this->openFromPool($this->masters, $this->masterConfig);
            if ($connection !== null) {
                $this->pdo = $connection->pdo;
                return;
            } else {
                throw new Exception('None of the master DB servers is available.');
            }
        }

        if (empty($this->dsn)) {
            throw new Exception('Connection::dsn cannot be empty.');
        }
        $token = 'Opening DB connection: ' . $this->dsn;
        try {
            Rock::trace('db', $token);
            Rock::beginProfile('db', $token);
            $this->pdo = $this->createPdoInstance();
            $this->initConnection();
            Rock::endProfile('db', $token);
        } catch (\PDOException $e) {
            Rock::endProfile('db', $token);
            throw new Exception($e->getMessage(), [], $e);
        }
    }

    /**
     * Closes the currently active DB connection.
     * It does nothing if the connection is already closed.
     */
    public function close()
    {
        if ($this->pdo !== null) {
            Rock::trace('db', 'Closing DB connection: ' . $this->dsn);
            $this->pdo = null;
            $this->_schema = null;
            $this->_transaction = null;
        }

        if ($this->_slave) {
            $this->_slave->close();
            $this->_slave = null;
        }
    }

    /**
     * Creates the PDO instance.
     * 
     * This method is called by {@see \rock\db\Connection::open()} to establish a DB connection.
     * The default implementation will create a PHP PDO instance.
     * You may override this method if the default PDO needs to be adapted for certain DBMS.
     * @return PDO the pdo instance
     */
    protected function createPdoInstance()
    {
        $pdoClass = $this->pdoClass;
        if ($pdoClass === null) {
            $pdoClass = 'PDO';
            if ($this->_driverName !== null) {
                $driver = $this->_driverName;
            } elseif (($pos = strpos($this->dsn, ':')) !== false) {
                $driver = strtolower(substr($this->dsn, 0, $pos));
            }
            if (isset($driver) && ($driver === 'mssql' || $driver === 'dblib' || $driver === 'sqlsrv')) {
                $pdoClass = \rock\db\mssql\PDO::className();
            }
        }

        return new $pdoClass($this->dsn, $this->username, $this->password, $this->attributes);
    }

    /**
     * Initializes the DB connection.
     * 
     * This method is invoked right after the DB connection is established.
     * The default implementation turns on `PDO::ATTR_EMULATE_PREPARES`
     * if {@see \rock\db\Connection::$emulatePrepare} is true, and sets the database {@see \rock\db\Connection::$charset} if it is not empty.
     * It then triggers an {@see \rock\db\Connection::EVENT_AFTER_OPEN} event.
     */
    protected function initConnection()
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        if ($this->emulatePrepare !== null && constant('PDO::ATTR_EMULATE_PREPARES')) {
            $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, $this->emulatePrepare);
        }
        if ($this->charset !== null && in_array($this->getDriverName(), ['pgsql', 'mysql', 'mysqli', 'cubrid'])) {
            $this->pdo->exec('SET NAMES ' . $this->pdo->quote($this->charset));
        }        $this->trigger(self::EVENT_AFTER_OPEN);
        //Event::trigger($this, self::EVENT_AFTER_OPEN);
    }

    /**
     * Creates a command for execution.
     * @param string $sql the SQL statement to be executed
     * @param array $params the parameters to be bound to the SQL statement
     * @return Command the DB command
     */
    public function createCommand($sql = null, $params = [])
    {
        //$this->open();
        $command = new Command([
            'connection' => $this,
            'sql' => $sql,
        ]);

        return $command->bindValues($params);
    }

    /**
     * Returns the currently active transaction.
     * @return Transaction the currently active transaction. Null if no active transaction.
     */
    public function getTransaction()
    {
        return $this->_transaction && $this->_transaction->getIsActive() ? $this->_transaction : null;
    }

    /**
     * Starts a transaction.
     * @param string|null $isolationLevel The isolation level to use for this transaction.
     * See {@see \rock\db\Transaction::begin()} for details.
     * @return Transaction the transaction initiated
     */
    public function beginTransaction($isolationLevel = null)
    {
        $this->open();

        if (($transaction = $this->getTransaction()) === null) {
            $transaction = $this->_transaction = new Transaction(['connection' => $this]);
        }
        $transaction->begin($isolationLevel);

        return $transaction;
    }

    /**
     * Executes callback provided in a transaction.
     *
     * @param callable $callback a valid PHP callback that performs the job. Accepts connection instance as parameter.
     * @param string|null $isolationLevel The isolation level to use for this transaction.
     * See {@see \rock\db\Transaction::begin()} for details.
     * @throws \Exception
     * @return mixed result of callback function
     */
    public function transaction(callable $callback, $isolationLevel = null)
    {
        $transaction = $this->beginTransaction($isolationLevel);

        try {
            $result = call_user_func($callback, $this);
            if ($transaction->isActive) {
                $transaction->commit();
            }
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }

        return $result;
    }

    /**
     * Returns the schema information for the database opened by this connection.
     * @return Schema the schema information for the database opened by this connection.
     * @throws Exception if there is no support for the current driver type
     */
    public function getSchema()
    {
        if ($this->_schema !== null) {
            return $this->_schema;
        } else {
            $driver = $this->getDriverName();
            if (isset($this->schemaMap[$driver])) {
                $this->_schema = is_array($this->schemaMap[$driver]) ? $this->schemaMap[$driver]['class'] : $this->schemaMap[$driver];
                return $this->_schema = new $this->_schema(['connection'=>$this]);
            } else {
                throw new Exception("Connection does not support reading schema information for '$driver' DBMS.");
            }
        }
    }

    /**
     * Returns the query builder for the current DB connection.
     * @return QueryBuilder the query builder for the current DB connection.
     */
    public function getQueryBuilder()
    {
        return $this->getSchema()->getQueryBuilder();
    }

    /**
     * Obtains the schema information for the named table.
     * @param string $name table name.
     * @param boolean $refresh whether to reload the table schema even if it is found in the cache.
     * @return TableSchema table schema information. Null if the named table does not exist.
     */
    public function getTableSchema($name, $refresh = false)
    {
        return $this->getSchema()->getTableSchema($name, $refresh);
    }

    /**
     * Returns the ID of the last inserted row or sequence value.
     *
     * @param string $sequenceName name of the sequence object (required by some DBMS)
     * @return string the row ID of the last row inserted, or the last value retrieved from the sequence object
     * @see http://www.php.net/manual/en/function.PDO-lastInsertId.php
     */
    public function getLastInsertID($sequenceName = '')
    {
        return $this->getSchema()->getLastInsertID($sequenceName);
    }

    /**
     * Quotes a string value for use in a query.
     *
     * Note that if the parameter is not a string, it will be returned without change.
     *
     * @param string $value string to be quoted
     * @return string the properly quoted string
     * @see http://www.php.net/manual/en/function.PDO-quote.php
     */
    public function quoteValue($value)
    {
        return $this->getSchema()->quoteValue($value);
    }

    /**
     * Quotes a table name for use in a query.
     *
     * If the table name contains schema prefix, the prefix will also be properly quoted.
     * If the table name is already quoted or contains special characters including '(', '[[' and '{{',
     * then this method will do nothing.
     * @param string $name table name
     * @return string the properly quoted table name
     */
    public function quoteTableName($name)
    {
        return $this->getSchema()->quoteTableName($name);
    }

    /**
     * Quotes a column name for use in a query.
     *
     * If the column name contains prefix, the prefix will also be properly quoted.
     * If the column name is already quoted or contains special characters including '(', '[[' and '{{',
     * then this method will do nothing.
     * @param string $name column name
     * @return string the properly quoted column name
     */
    public function quoteColumnName($name)
    {
        return $this->getSchema()->quoteColumnName($name);
    }

    /**
     * Processes a SQL statement by quoting table and column names that are enclosed within double brackets.
     *
     * Tokens enclosed within double curly brackets are treated as table names, while
     * tokens enclosed within double square brackets are column names. They will be quoted accordingly.
     * Also, the percentage character "%" at the beginning or ending of a table name will be replaced
     * with {@see \rock\db\Connection::$tablePrefix}.
     * @param string $sql the SQL to be quoted
     * @return string the quoted SQL
     */
    public function quoteSql($sql)
    {
        return preg_replace_callback(
            '/(\\{\\{(%?[\w\-\. ]+%?)\\}\\}|\\[\\[([\w\-\. ]+)\\]\\])/',
            function ($matches) {
                if (isset($matches[3])) {
                    return $this->quoteColumnName($matches[3]);
                } else {
                    return str_replace('%', $this->tablePrefix, $this->quoteTableName($matches[2]));
                }
            },
            $sql
        );
    }

    /**
     * Returns the name of the DB driver. Based on the the current {@see \rock\db\Connection::$dsn}, in case it was not set explicitly
     * by an end user.
     * @return string name of the DB driver
     */
    public function getDriverName()
    {
        if ($this->_driverName === null) {
            if (($pos = strpos($this->dsn, ':')) !== false) {
                $this->_driverName = strtolower(substr($this->dsn, 0, $pos));
            } else {
                //$this->open();
                $this->_driverName = strtolower($this->getSlavePdo()->getAttribute(PDO::ATTR_DRIVER_NAME));
            }
        }
        return $this->_driverName;
    }

    /**
     * Changes the current driver name.
     * @param string $driverName name of the DB driver
     */
    public function setDriverName($driverName)
    {
        $this->_driverName = strtolower($driverName);
    }

    /**
     * Returns the PDO instance for the currently active slave connection.
     *
     * When {@see \rock\db\Connection::$enableSlaves} is true, one of the slaves will be used for read queries, and its PDO instance
     * will be returned by this method.
     * @param boolean $fallbackToMaster whether to return a master PDO in case none of the slave connections is available.
     * @return PDO the PDO instance for the currently active slave connection. Null is returned if no slave connection
     * is available and `$fallbackToMaster` is false.
     */
    public function getSlavePdo($fallbackToMaster = true)
    {
        $connection = $this->getSlave(false);
        if ($connection === null) {
            return $fallbackToMaster ? $this->getMasterPdo() : null;
        } else {
            return $connection->pdo;
        }
    }

    /**
     * Returns the PDO instance for the currently active master connection.
     *
     * This method will open the master DB connection and then return {@see \rock\db\Connection::$pdo}.
     * @return PDO the PDO instance for the currently active master connection.
     */
    public function getMasterPdo()
    {
        $this->open();
        return $this->pdo;
    }

    /**
     * Returns the currently active slave connection.
     *
     * If this method is called the first time, it will try to open a slave connection when {@see \rock\db\Connection::$enableSlaves} is true.
     * @param boolean $fallbackToMaster whether to return a master connection in case there is no slave connection available.
     * @return Connection the currently active slave connection. Null is returned if there is slave available and
     * `$fallbackToMaster` is false.
     */
    public function getSlave($fallbackToMaster = true)
    {
        if (!$this->enableSlaves) {
            return $fallbackToMaster ? $this : null;
        }

        if ($this->_slave === false) {
            $this->_slave = $this->openFromPool($this->slaves, $this->slaveConfig);
        }

        return $this->_slave === null && $fallbackToMaster ? $this : $this->_slave;
    }

    /**
     * Executes the provided callback by using the master connection.
     *
     * This method is provided so that you can temporarily force using the master connection to perform
     * DB operations even if they are read queries. For example,
     *
     * ```php
     * $result = $db->useMaster(function ($db) {
     *     return $db->createCommand('SELECT * FROM user LIMIT 1')->queryOne();
     * });
     * ```
     *
     * @param callable $callback a PHP callable to be executed by this method. Its signature is
     * `function (Connection $db)`. Its return value will be returned by this method.
     * @return mixed the return value of the callback
     */
    public function useMaster(callable $callback)
    {
        $enableSlave = $this->enableSlaves;
        $this->enableSlaves = false;
        $result = call_user_func($callback, $this);
        $this->enableSlaves = $enableSlave;
        return $result;
    }

    /**
     * Opens the connection to a server in the pool.
     *
     * This method implements the load balancing among the given list of the servers.
     * @param array $pool the list of connection configurations in the server pool
     * @param array $sharedConfig the configuration common to those given in `$pool`.
     * @return Connection the opened DB connection, or null if no server is available
     * @throws Exception if a configuration does not specify "dsn"
     */
    protected function openFromPool(array $pool, array $sharedConfig)
    {
        if (empty($pool)) {
            return null;
        }

        if (!isset($sharedConfig['class'])) {
            $sharedConfig['class'] = get_class($this);
        }

        $cache = is_string($this->serverStatusCache) ? Container::load($this->serverStatusCache) : $this->serverStatusCache;

        shuffle($pool);

        foreach ($pool as $config) {
            $config = array_merge($sharedConfig, $config);
            if (empty($config['dsn'])) {
                throw new Exception('The "dsn" option must be specified.');
            }

            $key = [__METHOD__, $config['dsn']];
            if ($cache instanceof CacheInterface && $cache->get($key)) {
                // should not try this dead server now
                continue;
            }

            /* @var $connection Connection */
            $connection = Container::load($config);

            try {
                $connection->open();
                return $connection;
            } catch (\Exception $e) {
                Rock::warning("Connection ({$config['dsn']}) failed: " . $e->getMessage(), __METHOD__);
                if ($cache instanceof CacheInterface) {
                    // mark this server as dead and only retry it after the specified interval
                    $cache->set($key, 1, $this->serverRetryInterval);
                }
            }
        }

        return null;
    }
}
