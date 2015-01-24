<?php
namespace rock\mongodb;

use rock\base\ComponentsTrait;
use rock\base\ModelEvent;
use rock\cache\CacheInterface;
use rock\db\CacheTrait;
use rock\db\QueryInterface;
use rock\db\QueryTrait;
use rock\di\Container;
use rock\helpers\Json;
use rock\helpers\Trace;

/**
 * Query represents Mongo "find" operation.
 *
 * Query provides a set of methods to facilitate the specification of "find" command.
 * These methods can be chained together.
 *
 * For example,
 *
 * ```php
 * $query = new Query;
 * // compose the query
 * $query->select(['name', 'status'])
 *     ->from('customer')
 *     ->limit(10);
 * // execute the query
 * $rows = $query->all();
 * ```
 */
class Query implements QueryInterface
{
    use ComponentsTrait {
        ComponentsTrait::__call as parentCall;
    }
    use QueryTrait;
    use CacheTrait;

    /**
     * @event Event an event that is triggered after the record is created and populated with query result.
     */
    const EVENT_BEFORE_FIND = 'beforeFind';

    /**
     * @var array the fields of the results to return. For example, `['name', 'group_id']`.
     * The "_id" field is always returned. If not set, if means selecting all columns.
     * @see select()
     */
    public $select = [];
    /**
     * @var string|array the collection to be selected from. If string considered as the name of the collection
     * inside the default database. If array - first element considered as the name of the database,
     * second - as name of collection inside that database
     * @see from()
     */
    public $from;
    /**
     * @var Connection|string
     */
    public $connection = 'mongodb';


    /**
     * Returns the Mongo collection for this query.
     *
     * @param Connection $connection Mongo connection.
     * @return Collection collection instance.
     */
    public function getCollection($connection = null)
    {
        if ($connection === null) {
            $this->connection = is_string($this->connection) ? Container::load($this->connection) : $this->connection;
        } else {
            $this->connection = $connection;
        }
        $this->calculateCacheParams($this->connection);
        return $this->connection->getCollection($this->from);
    }

    /**
     * Sets the list of fields of the results to return.
     * @param array $fields fields of the results to return.
     * @return static the query object itself.
     */
    public function select(array $fields)
    {
        $this->select = $fields;

        return $this;
    }

    /**
     * Sets the collection to be selected from.
     * @param string|array the collection to be selected from. If string considered as the name of the collection
     * inside the default database. If array - first element considered as the name of the database,
     * second - as name of collection inside that database
     * @return static the query object itself.
     */
    public function from($collection)
    {
        $this->from = $collection;

        return $this;
    }

    /**
     * Builds the Mongo cursor for this query.
     *
     * @param Connection $connection the database connection used to execute the query.
     * @return \MongoCursor mongo cursor instance.
     */
    protected function buildCursor($connection = null)
    {
        $cursor = $this->getCollection($connection)->find($this->composeCondition(), $this->composeSelectFields());
        if (!empty($this->orderBy)) {
            $cursor->sort($this->composeSort());
        }
        $cursor->limit($this->limit);
        $cursor->skip($this->offset);

        return $cursor;
    }

    /**
     * Fetches rows from the given Mongo cursor.
     *
*@param \MongoCursor $cursor Mongo cursor instance to fetch data from.
     * @param boolean $all whether to fetch all rows or only first one.
     * @param string|callable $indexBy the column name or PHP callback,
     * by which the query results should be indexed by.
     * @throws MongoException on failure.
     * @return array|null result.
     */
    protected function fetchRows($cursor, $all = true, $indexBy = null)
    {
        $rawQuery = 'find(' . Json::encode($cursor->info()) . ')';
        //Rock::info($token, __METHOD__);
        $token = [
            //'dsn' => $connection->dsn,
            'query' => $rawQuery,
            'valid' => true,
            'cache' => false,
        ];
        Trace::beginProfile('mongodb.query', $token);

        $cache = $cacheKey = null;
        if (($result = $this->getCache($rawQuery, $token, $all, $indexBy, $cache, $cacheKey)) !== false) {
            return $result;
        }

        try {
            $result = $this->fetchRowsInternal($cursor, $all, $indexBy);
            $this->setCache($result, $cache, $cacheKey);
            Trace::endProfile('mongodb.query', $token);
            Trace::trace('mongodb.query', $token);
            return $result;
        } catch (\Exception $e) {
            $message = $e->getMessage() . "\nThe query being executed was: $rawQuery";
            Trace::endProfile('mongodb.query', $token);
            $token['valid']     = false;
            $token['exception'] = DEBUG === true ? $e : $message;
            Trace::trace('mongodb.query', $token);

            throw new MongoException($message, [], $e);
        }
    }

    /**
     * @param \MongoCursor $cursor Mongo cursor instance to fetch data from.
     * @param boolean $all whether to fetch all rows or only first one.
     * @param string|callable $indexBy value to index by.
     * @return array|null result.
     * @see Query::fetchRows()
     */
    protected function fetchRowsInternal($cursor, $all, $indexBy)
    {
        $result = [];
        if ($all) {
            foreach ($cursor as $row) {
                if ($indexBy !== null) {
                    if (is_string($indexBy)) {
                        $key = $row[$indexBy];
                    } else {
                        $key = call_user_func($indexBy, $row);
                    }
                    $result[$key] = $row;
                } else {
                    $result[] = $row;
                }
            }
        } else {
            if ($cursor->hasNext()) {
                $result = $cursor->getNext();
            } else {
                $result = null;
            }
        }

        return $result;
    }

    /**
     * Executes the query and returns all results as an array.
     * @param Connection $connection the Mongo connection used to execute the query.
     * If this parameter is not given, the `mongodb` application component will be used.
     * @return array the query results. If the query results in nothing, an empty array will be returned.
     */
    public function all($connection = null)
    {
        // before
        if (!$this->beforeFind()) {
            return [];
        }

        $cursor = $this->buildCursor($connection);

        return $this->fetchRows($cursor, true, $this->indexBy);
    }

    /**
     * Executes the query and returns a single row of result.
     * @param Connection $connection the Mongo connection used to execute the query.
     * If this parameter is not given, the `mongodb` application component will be used.
     * @return array|null the first row (in terms of an array) of the query result. False is returned if the query
     * results in nothing.
     */
    public function one($connection = null)
    {
        // before
        if (!$this->beforeFind()) {
            return null;
        }

        $cursor = $this->buildCursor($connection);

        return $this->fetchRows($cursor, false);
    }

    /**
     * Performs 'findAndModify' query and returns a single row of result.
     *
     * @param array $update update criteria
     * @param array $options list of options in format: optionName => optionValue.
     * @param Connection $connection the Mongo connection used to execute the query.
     * @return array|null the original document, or the modified document when $options['new'] is set.
     */
    public function modify($update, $options = [], $connection = null)
    {
        // before
        if (!$this->beforeFind()) {
            return null;
        }

        $collection = $this->getCollection($connection);
        if (!empty($this->orderBy)) {
            $options['sort'] = $this->composeSort();
        }

        return $collection->findAndModify($this->composeCondition(), $update, $this->composeSelectFields(), $options);
    }

    /**
     * Returns the number of records.
     *
     * @param string $q kept to match {@see \rock\db\QueryInterface}, its value is ignored.
     * @param Connection $connection the Mongo connection used to execute the query.
     * If this parameter is not given, the `mongodb` application component will be used.
     * @return integer number of records
     * @throws MongoException on failure.
     */
    public function count($q = '*', $connection = null)
    {
        $cursor = $this->buildCursor($connection);
        $rawQuery = 'find(' . Json::encode($cursor->info()) . ')';
        $token = [
            //'dsn' => $connection->dsn,
            'query' => $rawQuery,
            'valid' => true,
            'cache' => false,
        ];
        //Rock::info($token, __METHOD__);
        Trace::beginProfile('mongodb.query', $token);

        $cache = $cacheKey = null;
        if (($result = $this->getCache($rawQuery, $token, null, null, $cache, $cacheKey)) !== false) {
            return $result;
        }
        try {
            $result = $cursor->count();
            $this->setCache($result, $cache, $cacheKey);
            Trace::endProfile('mongodb.query', $token);
            Trace::trace('mongodb.query', $token);

            return $result;
        } catch (\Exception $e) {
            $message = $e->getMessage() . "\nThe query being executed was: $rawQuery";
            Trace::endProfile('mongodb.query', $token);
            $token['valid']     = false;
            $token['exception'] = DEBUG === true ? $e : $message;
            Trace::trace('mongodb.query', $token);

            throw new MongoException($message, [], $e);
        }
    }

    /**
     * Returns a value indicating whether the query result contains any row of data.
     *
     * @param Connection $connection the Mongo connection used to execute the query.
     * If this parameter is not given, the `mongodb` application component will be used.
     * @return boolean whether the query result contains any row of data.
     */
    public function exists($connection = null)
    {
        return (bool)$this->one($connection);
    }

    /**
     * Returns the sum of the specified column values.
     *
     * @param string $q the column name.
     * Make sure you properly quote column names in the expression.
     * @param Connection $connection the Mongo connection used to execute the query.
     * If this parameter is not given, the `mongodb` application component will be used.
     * @return integer the sum of the specified column values
     */
    public function sum($q, $connection = null)
    {
        return $this->aggregate($q, 'sum', $connection);
    }

    /**
     * Returns the average of the specified column values.
     *
     * @param string $q the column name.
     * Make sure you properly quote column names in the expression.
     * @param Connection $connection the Mongo connection used to execute the query.
     * If this parameter is not given, the `mongodb` application component will be used.
     * @return integer the average of the specified column values.
     */
    public function average($q, $connection = null)
    {
        return $this->aggregate($q, 'avg', $connection);
    }

    /**
     * Returns the minimum of the specified column values.
     *
     * @param string $q the column name.
     * Make sure you properly quote column names in the expression.
     * @param Connection $connection the database connection used to generate the SQL statement.
     * If this parameter is not given, the `db` application component will be used.
     * @return integer the minimum of the specified column values.
     */
    public function min($q, $connection = null)
    {
        return $this->aggregate($q, 'min', $connection);
    }

    /**
     * Returns the maximum of the specified column values.
     *
     * @param string $q the column name.
     * Make sure you properly quote column names in the expression.
     * @param Connection $connection the Mongo connection used to execute the query.
     * If this parameter is not given, the `mongodb` application component will be used.
     * @return integer the maximum of the specified column values.
     */
    public function max($q, $connection = null)
    {
        return $this->aggregate($q, 'max', $connection);
    }

    /**
     * Performs the aggregation for the given column.
     *
     * @param string $column column name.
     * @param string $operator aggregation operator.
     * @param Connection $connection the database connection used to execute the query.
     * @return integer aggregation result.
     */
    protected function aggregate($column, $operator, $connection)
    {
        $collection = $this->getCollection($connection);
        $pipelines = [];
        if ($this->where !== null) {
            $pipelines[] = ['$match' => $collection->buildCondition($this->where)];
        }
        $pipelines[] = [
            '$group' => [
                '_id' => '1',
                'total' => [
                    '$' . $operator => '$' . $column
                ],
            ]
        ];
        $result = $collection->aggregate($pipelines);
        if (array_key_exists(0, $result)) {
            return $result[0]['total'];
        } else {
            return 0;
        }
    }

    /**
     * Returns a list of distinct values for the given column across a collection.
     *
     * @param string $q column to use.
     * @param Connection $connection the Mongo connection used to execute the query.
     * If this parameter is not given, the `mongodb` application component will be used.
     * @return array array of distinct values
     */
    public function distinct($q, $connection = null)
    {
        $collection = $this->getCollection($connection);
        if ($this->where !== null) {
            $condition = $this->where;
        } else {
            $condition = [];
        }
        $result = $collection->distinct($q, $condition);
        if ($result === false) {
            return [];
        } else {
            return $result;
        }
    }

    /**
     * This method is called when the AR object is created and populated with the query result.
     *
     * The default implementation will trigger an {@see \rock\db\BaseActiveRecord::EVENT_BEFORE_FIND} event.
     * When overriding this method, make sure you call the parent implementation to ensure the
     * event is triggered.
     */
    public function beforeFind()
    {
        $event = new ModelEvent;
        $this->trigger(self::EVENT_BEFORE_FIND, $event);
        return $event->isValid;
    }

    /**
     * Composes condition from raw {@see \rock\db\QueryTrait::$where} value.
     * @return array conditions.
     */
    private function composeCondition()
    {
        if ($this->where === null) {
            return [];
        } else {
            return $this->where;
        }
    }

    /**
     * Composes select fields from raw {@see \rock\mongodb\Query::$select} value.
     * @return array select fields.
     */
    private function composeSelectFields()
    {
        $selectFields = [];
        if (!empty($this->select)) {
            foreach ($this->select as $fieldName) {
                $selectFields[$fieldName] = true;
            }
        }
        return $selectFields;
    }

    /**
     * Composes sort specification from raw {@see \rock\db\QueryTrait::$orderBy} value.
     * @return array sort specification.
     */
    private function composeSort()
    {
        $sort = [];
        foreach ($this->orderBy as $fieldName => $sortOrder) {
            $sort[$fieldName] = $sortOrder === SORT_DESC ? \MongoCollection::DESCENDING : \MongoCollection::ASCENDING;
        }
        return $sort;
    }

    protected function getCache($rawQuery, $token, $all, $indexBy, &$cache, &$cacheKey)
    {
        /** @var $cache CacheInterface */
        if ($this->connection->enableQueryCache) {
            $cache = is_string($this->connection->queryCache)
                ? Container::load($this->connection->queryCache)
                : $this->connection->queryCache;
        }

        if ($cache instanceof CacheInterface) {
            $cacheKey = json_encode([__METHOD__, $this->connection->dsn, $rawQuery]);
            if (($result = $cache->get($cacheKey)) !== false) {
                Trace::increment('cache.mongodb', 'Cache query MongoDB connection: ' . $this->connection->dsn);
                Trace::endProfile('mongodb.query', $token);
                $token['cache'] = true;
                Trace::trace('mongodb.query', $token);

                return is_array($result) ? $this->fetchRowsAsArrayInternal($result, $all, $indexBy) : $result;
            }
        }

        return false;
    }

    protected function setCache($result, $cache, $cacheKey)
    {
        if (isset($cache, $cacheKey) && $cache instanceof CacheInterface) {
            $cache->set($cacheKey,
                        $result,
                        $this->connection->queryCacheExpire,
                        $this->connection->queryCacheTags ? : (array)$this->from
            );
        }
    }

    protected function fetchRowsAsArrayInternal($rows, $all, $indexBy)
    {
        $result = [];
        if ($all) {
            foreach ($rows as $row) {
                if ($indexBy !== null) {
                    if (is_string($indexBy)) {
                        $key = $row[$indexBy];
                    } else {
                        $key = call_user_func($indexBy, $row);
                    }
                    $result[$key] = $row;
                } else {
                    $result[] = $row;
                }
            }
            return $result;
        }

        return $rows;
    }
}
