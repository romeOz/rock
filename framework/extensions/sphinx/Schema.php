<?php
namespace rock\sphinx;

use rock\base\ObjectTrait;
use rock\cache\CacheInterface;
use rock\helpers\Helper;
use rock\Rock;

/**
 * Schema represents the Sphinx schema information.
 *
 * @property string[] $indexNames All index names in the Sphinx. This property is read-only.
 * @property IndexSchema[] $indexSchemas The metadata for all indexes in the Sphinx. Each array element is an
 * instance of {@see \rock\sphinx\IndexSchema} or its child class. This property is read-only.
 * @property array $indexTypes All index types in the Sphinx in format: index name => index type. This
 * property is read-only.
 * @property QueryBuilder $queryBuilder The query builder for this connection. This property is read-only.
 */
class Schema 
{
    use ObjectTrait;
    /**
     * The followings are the supported abstract column data types.
     */
    const TYPE_PK = 'pk';
    const TYPE_STRING = 'string';
    const TYPE_INTEGER = 'integer';
    const TYPE_BIGINT = 'bigint';
    const TYPE_FLOAT = 'float';
    const TYPE_TIMESTAMP = 'timestamp';
    const TYPE_BOOLEAN = 'boolean';

    /**
     * @var Connection the Sphinx connection
     */
    public $connection;
    /**
     * @var array list of ALL index names in the Sphinx
     */
    private static $_indexNames;
    /**
     * @var array list of ALL index types in the Sphinx (index name => index type)
     */
    private static $_indexTypes;
    /**
     * @var array list of loaded index metadata (index name => IndexSchema)
     */
    private static $_indexes = [];
    /**
     * @var QueryBuilder the query builder for this Sphinx connection
     */
    private $_builder;

    /**
     * @var array mapping from physical column types (keys) to abstract column types (values)
     */
    public $typeMap = [
        'field' => self::TYPE_STRING,
        'string' => self::TYPE_STRING,
        'ordinal' => self::TYPE_STRING,
        'integer' => self::TYPE_INTEGER,
        'int' => self::TYPE_INTEGER,
        'uint' => self::TYPE_INTEGER,
        'bigint' => self::TYPE_BIGINT,
        'timestamp' => self::TYPE_TIMESTAMP,
        'bool' => self::TYPE_BOOLEAN,
        'float' => self::TYPE_FLOAT,
        'mva' => self::TYPE_INTEGER,
    ];

    /**
     * Loads the metadata for the specified index.
     * @param string $name index name
     * @return IndexSchema driver dependent index metadata. Null if the index does not exist.
     */
    protected function loadIndexSchema($name)
    {
        $index = new IndexSchema;
        $this->resolveIndexNames($index, $name);
        $this->resolveIndexType($index);

        if ($this->findColumns($index)) {
            return $index;
        } else {
            return null;
        }
    }

    /**
     * Resolves the index name.
     * @param IndexSchema $index the index metadata object
     * @param string $name the index name
     */
    protected function resolveIndexNames($index, $name)
    {
        $index->name = str_replace('`', '', $name);
    }

    /**
     * Resolves the index name.
     * @param IndexSchema $index the index metadata object
     */
    protected function resolveIndexType($index)
    {
        $indexTypes = $this->getIndexTypes();
        $index->type = array_key_exists($index->name, $indexTypes) ? $indexTypes[$index->name] : 'unknown';
        $index->isRuntime = ($index->type == 'rt');
    }

    /**
     * Obtains the metadata for the named index.
     * @param string $name index name. The index name may contain schema name if any. Do not quote the index name.
     * @param boolean $refresh whether to reload the index schema even if it is found in the cache.
     * @return IndexSchema index metadata. Null if the named index does not exist.
     */
    public function getIndexSchema($name, $refresh = false)
    {
        $name = $this->removeAliasEntity($name);
        if (isset(self::$_indexes[$name]) && !$refresh) {
            return self::$_indexes[$name];
        }

        $connection = $this->connection;
        $realName = $this->getRawIndexName($name);

        if ($connection->enableSchemaCache === true && !in_array($name, $connection->schemaCacheExclude, true)) {
            /** @var CacheInterface $cache */
            $cache = is_string($connection->schemaCache) ? Rock::factory($connection->schemaCache) : $connection->schemaCache;

            if ($cache instanceof CacheInterface) {
                $cacheKey = serialize($this->getCacheKey($name));
                if ($refresh || ($table = $cache->get($cacheKey)) === false) {
                    $table = $this->loadIndexSchema($realName);
                    self::$_indexes[$name] = $table;
                    if ($table !== null) {
                        $cache->set($cacheKey, $table, $connection->schemaCacheExpire, [$this->getCacheGroup()]);
                    }
                } else {
                    self::$_indexes[$name] = $table;
                }

                return self::$_indexes[$name];
            }
        }

        return self::$_indexes[$name] = $this->loadIndexSchema($realName);
    }

    protected function removeAliasEntity($table)
    {
        if (preg_match('/^(.*?)(?i:\s+as\s+|\s+)([\w\-_\.]+)$/', $table, $matches)) {
            return $matches[1];
        } else {
            return $table;
        }
    }

    /**
     * Returns the cache key for the specified index name.
     * @param string $name the index name
     * @return mixed the cache key
     */
    protected function getCacheKey($name)
    {
        return [
            __CLASS__,
            $this->connection->dsn,
            $this->connection->username,
            $name,
        ];
    }

    /**
     * Returns the cache group name.
     * This allows {@see \rock\sphinx\Schema::refresh()} to invalidate all cached index schemas.
     * @return string the cache group name
     */
    protected function getCacheGroup()
    {
        return Helper::hash(
            [
                __CLASS__,
                $this->connection->dsn,
                $this->connection->username,
            ],
            Helper::SERIALIZE_JSON
        );
    }

    /**
     * Returns the metadata for all indexes in the database.
     * @param boolean $refresh whether to fetch the latest available index schemas. If this is false,
     * cached data may be returned if available.
     * @return IndexSchema[] the metadata for all indexes in the Sphinx.
     * Each array element is an instance of {@see \rock\sphinx\IndexSchema} or its child class.
     */
    public function getIndexSchemas($refresh = false)
    {
        $indexes = [];
        foreach ($this->getIndexNames($refresh) as $name) {
            if (($index = $this->getIndexSchema($name, $refresh)) !== null) {
                $indexes[] = $index;
            }
        }

        return $indexes;
    }

    /**
     * Returns all index names in the Sphinx.
     * @param boolean $refresh whether to fetch the latest available index names. If this is false,
     * index names fetched previously (if available) will be returned.
     * @return string[] all index names in the Sphinx.
     */
    public function getIndexNames($refresh = false)
    {
        if (!isset(self::$_indexNames) || $refresh) {
            $this->initIndexesInfo();
        }

        return self::$_indexNames;
    }

    /**
     * Returns all index types in the Sphinx.
     * @param boolean $refresh whether to fetch the latest available index types. If this is false,
     * index types fetched previously (if available) will be returned.
     * @return array all index types in the Sphinx in format: index name => index type.
     */
    public function getIndexTypes($refresh = false)
    {
        if (!isset(self::$_indexTypes) || $refresh) {
            $this->initIndexesInfo();
        }

        return self::$_indexTypes;
    }

    /**
     * Initializes information about name and type of all index in the Sphinx.
     */
    protected function initIndexesInfo()
    {
        self::$_indexNames = [];
        self::$_indexTypes = [];
        $indexes = $this->findIndexes();
        foreach ($indexes as $index) {
            $indexName = $index['Index'];
            self::$_indexNames[] = $indexName;
            self::$_indexTypes[$indexName] = $index['Type'];
        }
    }

    /**
     * Returns all index names in the Sphinx.
     * @return array all index names in the Sphinx.
     */
    protected function findIndexes()
    {
        $sql = 'SHOW TABLES';
        $enableQueryCache = $this->connection->enableQueryCache;
        $this->connection->enableQueryCache = false;
        $result = $this->connection->createCommand($sql)->queryAll();
        $this->connection->enableQueryCache = $enableQueryCache;
        return $result;
    }

    /**
     * @return QueryBuilder the query builder for this connection.
     */
    public function getQueryBuilder()
    {
        if ($this->_builder === null) {
            $this->_builder = $this->createQueryBuilder();
        }

        return $this->_builder;
    }

    /**
     * Determines the PDO type for the given PHP data value.
     * @param mixed $data the data whose PDO type is to be determined
     * @return integer the PDO type
     * @see http://www.php.net/manual/en/pdo.constants.php
     */
    public function getPdoType($data)
    {
        static $typeMap = [
            // php type => PDO type
            'boolean' => \PDO::PARAM_BOOL,
            'integer' => \PDO::PARAM_INT,
            'string' => \PDO::PARAM_STR,
            'resource' => \PDO::PARAM_LOB,
            'NULL' => \PDO::PARAM_NULL,
        ];
        $type = gettype($data);

        return isset($typeMap[$type]) ? $typeMap[$type] : \PDO::PARAM_STR;
    }

    /**
     * Refreshes the schema.
     * This method cleans up all cached index schemas so that they can be re-created later
     * to reflect the Sphinx schema change.
     */
    public function refresh()
    {
        /** @var CacheInterface $cache */
        $cache = is_string($this->connection->schemaCache) ? Rock::factory($this->connection->schemaCache) : $this->connection->schemaCache;
        if ($this->connection->enableSchemaCache && $cache instanceof CacheInterface) {
            $cache->removeTag($this->getCacheGroup());
        }
        self::$_indexNames = [];
        self::$_indexes = [];
    }

    /**
     * Creates a query builder for the Sphinx.
     * @return QueryBuilder query builder instance
     */
    public function createQueryBuilder()
    {
        return new QueryBuilder($this->connection);
    }

    /**
     * Quotes a string value for use in a query.
     * Note that if the parameter is not a string, it will be returned without change.
     * @param string $str string to be quoted
     * @return string the properly quoted string
     * @see http://www.php.net/manual/en/function.PDO-quote.php
     */
    public function quoteValue($str)
    {
        if (is_string($str)) {
            return $this->connection->getSlavePdo()->quote($str);
        } else {
            return $str;
        }
    }

    /**
     * Quotes a index name for use in a query.
     * If the index name contains schema prefix, the prefix will also be properly quoted.
     * If the index name is already quoted or contains '(' or '{{',
     * then this method will do nothing.
     * @param string $name index name
     * @return string the properly quoted index name
     * @see quoteSimpleTableName
     */
    public function quoteIndexName($name)
    {
        if (strpos($name, '(') !== false || strpos($name, '{{') !== false) {
            return $name;
        }

        return $this->quoteSimpleIndexName($name);
    }

    /**
     * Quotes a column name for use in a query.
     * If the column name contains prefix, the prefix will also be properly quoted.
     * If the column name is already quoted or contains '(', '[[' or '{{',
     * then this method will do nothing.
     * @param string $name column name
     * @return string the properly quoted column name
     * @see quoteSimpleColumnName
     */
    public function quoteColumnName($name)
    {
        if (strpos($name, '(') !== false || strpos($name, '[[') !== false || strpos($name, '{{') !== false) {
            return $name;
        }
        if (($pos = strrpos($name, '.')) !== false) {
            $prefix = $this->quoteIndexName(substr($name, 0, $pos)) . '.';
            $name = substr($name, $pos + 1);
        } else {
            $prefix = '';
        }

        return $prefix . $this->quoteSimpleColumnName($name);
    }

    /**
     * Quotes a index name for use in a query.
     * A simple index name has no schema prefix.
     * @param string $name index name
     * @return string the properly quoted index name
     */
    public function quoteSimpleIndexName($name)
    {
        return strpos($name, "`") !== false ? $name : "`" . $name . "`";
    }

    /**
     * Quotes a column name for use in a query.
     * A simple column name has no prefix.
     * @param string $name column name
     * @return string the properly quoted column name
     */
    public function quoteSimpleColumnName($name)
    {
        return strpos($name, '`') !== false || $name === '*' ? $name : '`' . $name . '`';
    }

    /**
     * Returns the actual name of a given index name.
     * This method will strip off curly brackets from the given index name
     * and replace the percentage character '%' with {@see \rock\sphinx\Connection::$tablePrefix}.
     * @param string $name the index name to be converted
     * @return string the real name of the given index name
     */
    public function getRawIndexName($name)
    {
        if (strpos($name, '{{') !== false) {
            $name = preg_replace('/\\{\\{(.*?)\\}\\}/', '\1', $name);

            return str_replace('%', $this->connection->tablePrefix, $name);
        } else {
            return $name;
        }
    }

    /**
     * Extracts the PHP type from abstract DB type.
     * @param ColumnSchema $column the column schema information
     * @return string PHP type name
     */
    protected function getColumnPhpType($column)
    {
        static $typeMap = [ // abstract type => php type
            'smallint' => 'integer',
            'integer' => 'integer',
            'uint' => 'integer',
            'bigint' => 'integer',
            'boolean' => 'boolean',
            'float' => 'double',
            'timestamp' => 'integer',
        ];
        if (isset($typeMap[$column->type])) {
            if ($column->type === 'bigint') {
                return PHP_INT_SIZE == 8 ? 'integer' : 'string';
            } elseif ($column->type === 'integer') {
                return PHP_INT_SIZE == 4 ? 'string' : 'integer';
            } else {
                return $typeMap[$column->type];
            }
        } else {
            return 'string';
        }
    }

    /**
     * Collects the metadata of index columns.
     * @param IndexSchema $index the index metadata
     * @return boolean whether the index exists in the database
     * @throws \Exception if DB query fails
     */
    protected function findColumns($index)
    {
        $sql = 'DESCRIBE ' . $this->quoteSimpleIndexName($index->name);
        try {
            $enableQueryCache = $this->connection->enableQueryCache;
            $this->connection->enableQueryCache = false;
            $columns = $this->connection->createCommand($sql)->queryAll();
            $this->connection->enableQueryCache = $enableQueryCache;
        } catch (\Exception $e) {
            if (($e instanceof SphinxException || $e instanceof \rock\db\DbException) && strpos($e->getMessage(), 'SQLSTATE[42S02') !== false) {
                // index does not exist
                // https://dev.mysql.com/doc/refman/5.5/en/error-messages-server.html#error_er_bad_table_error
                return false;
            }
            throw $e;
        }

        if (empty($columns[0]['Agent'])) {
            foreach ($columns as $info) {
                $column = $this->loadColumnSchema($info);
                $index->columns[$column->name] = $column;
                if ($column->isPrimaryKey) {
                    $index->primaryKey = $column->name;
                }
            }
        } else {
            // Distributed index :
            $agent = $this->getIndexSchema($columns[0]['Agent']);
            $index->columns = $agent->columns;
        }

        return true;
    }

    /**
     * Loads the column information into a {@see \rock\sphinx\ColumnSchema} object.
     * @param array $info column information
     * @return ColumnSchema the column schema object
     */
    protected function loadColumnSchema($info)
    {
        $column = new ColumnSchema;

        $column->name = $info['Field'];
        $column->dbType = $info['Type'];

        $column->isPrimaryKey = ($column->name == 'id');

        $type = $info['Type'];
        if (isset($this->typeMap[$type])) {
            $column->type = $this->typeMap[$type];
        } else {
            $column->type = self::TYPE_STRING;
        }

        $column->isField = ($type == 'field');
        $column->isAttribute = !$column->isField;

        $column->isMva = ($type == 'mva');

        $column->phpType = $this->getColumnPhpType($column);

        return $column;
    }
    /**
     * Returns a value indicating whether a SQL statement is for read purpose.
     * @param string $sql the SQL statement
     * @return boolean whether a SQL statement is for read purpose.
     */
    public function isReadQuery($sql)
    {
        $pattern = '/^\s*(SELECT|SHOW|DESCRIBE)\b/i';
        return preg_match($pattern, $sql) > 0;
    }
}
