<?php
namespace rock\db;

use rock\helpers\ArrayHelper;
use rock\helpers\Helper;
use rock\Rock;

/**
 * The BaseQuery trait represents the minimum method set of a database Query.
 *
 * It is supposed to be used in a class that implements the [[\rock\db\QueryInterface]].
 */
trait QueryTrait
{
    /**
     * @var string|array query condition. This refers to the WHERE clause in a SQL statement.
     * For example, ```age > 31 AND team = 1```.
     * @see where()
     */
    public $where;
    /**
     * @var integer maximum number of records to be returned. If not set or less than 0, it means no limit.
     */
    public $limit;
    /**
     * @var integer zero-based offset from where the records are to be returned. If not set or
     * less than 0, it means starting from the beginning.
     */
    public $offset;
    /**
     * @var array how to sort the query results. This is used to construct the ORDER BY clause in a SQL statement.
     * The array keys are the columns to be sorted by, and the array values are the corresponding sort directions which
     * can be either [SORT_ASC](http://php.net/manual/en/array.constants.php#constant.sort-asc)
     * or [SORT_DESC](http://php.net/manual/en/array.constants.php#constant.sort-desc).
     * The array may also contain [[Expression]] objects. If that is the case, the expressions
     * will be converted into strings without any change.
     */
    public $orderBy;
    /**
     * @var string|callable $column the name of the column by which the query results should be indexed by.
     * This can also be a callable (e.g. anonymous function) that returns the index value based on the given
     * row data. For more details, see [[indexBy()]]. This property is only used by [[QueryInterface::all()|all()]].
     */
    public $indexBy;


    /**
     * @param Connection|\rock\sphinx\Connection $connection DB/Sphinx connection instance
     * @return static the query object itself
     */
    public function setConnection(Connection $connection)
    {
        /** @var self|Query $this */
        $this->connection = $this->calculateCacheParams($connection);
        return $this;
    }

    /**
     * @return Connection|\rock\sphinx\Connection DB\Sphinx connection instance
     */
    public function getConnection()
    {
        /** @var self|Query $this */

        if (is_string($this->connection)) {
            $this->connection = Rock::factory($this->connection);
        }
        return $this->calculateCacheParams($this->connection);
    }

    /**
     * Sets the [[indexBy]] property.
     * @param string|callable $column the name of the column by which the query results should be indexed by.
     * This can also be a callable (e.g. anonymous function) that returns the index value based on the given
     * row data. The signature of the callable should be:
     *
     * ```php
     * function ($row)
     * {
     *     // return the index value corresponding to $row
     * }
     * ```
     *
     * @return static the query object itself
     */
    public function indexBy($column)
    {
        $this->indexBy = $column;
        return $this;
    }

    /**
     * Sets the WHERE part of the query.
     *
     * See [[QueryInterface::where()]] for detailed documentation.
     *
     * @param string|array $condition the conditions that should be put in the WHERE part.
     * @return static the query object itself
     * @see andWhere()
     * @see orWhere()
     */
    public function where($condition)
    {
        $this->where = $condition;
        return $this;
    }

    /**
     * Adds an additional WHERE condition to the existing one.
     * The new condition and the existing one will be joined using the 'AND' operator.
     * @param string|array $condition the new WHERE condition. Please refer to [[where()]]
     * on how to specify this parameter.
     * @return static the query object itself.
     * @see where()
     * @see orWhere()
     */
    public function andWhere($condition)
    {
        if ($this->where === null) {
            $this->where = $condition;
        } else {
            $this->where = ['and', $this->where, $condition];
        }
        return $this;
    }

    /**
     * Adds an additional WHERE condition to the existing one.
     * The new condition and the existing one will be joined using the 'OR' operator.
     * @param string|array $condition the new WHERE condition. Please refer to [[where()]]
     * on how to specify this parameter.
     * @return static the query object itself.
     * @see where()
     * @see andWhere()
     */
    public function orWhere($condition)
    {
        if ($this->where === null) {
            $this->where = $condition;
        } else {
            $this->where = ['or', $this->where, $condition];
        }
        return $this;
    }

    /**
     * Sets the WHERE part of the query but ignores [[isEmpty()|empty operands]].
     *
     * This method is similar to [[where()]]. The main difference is that this method will
     * remove [[isEmpty()|empty query operands]]. As a result, this method is best suited
     * for building query conditions based on filter values entered by users.
     *
     * The following code shows the difference between this method and [[where()]]:
     *
     * ```php
     * // WHERE `age`=:age
     * $query->filterWhere(['name' => null, 'age' => 20]);
     * // WHERE `age`=:age
     * $query->where(['age' => 20]);
     * // WHERE `name` IS NULL AND `age`=:age
     * $query->where(['name' => null, 'age' => 20]);
     * ```
     *
     * Note that unlike [[where()]], you cannot pass binding parameters to this method.
     *
     * @param array $condition the conditions that should be put in the WHERE part.
     * See [[where()]] on how to specify this parameter.
     * @return static the query object itself.
     * @see where()
     * @see andFilterWhere()
     * @see orFilterWhere()
     */
    public function filterWhere(array $condition)
    {
        $condition = $this->filterCondition($condition);
        if ($condition !== []) {
            $this->where($condition);
        }
        return $this;
    }

    /**
     * Adds an additional WHERE condition to the existing one but ignores [[isEmpty()|empty operands]].
     * The new condition and the existing one will be joined using the 'AND' operator.
     *
     * This method is similar to [[andWhere()]]. The main difference is that this method will
     * remove [[isEmpty()|empty query operands]]. As a result, this method is best suited
     * for building query conditions based on filter values entered by users.
     *
     * @param array $condition the new WHERE condition. Please refer to [[where()]]
     * on how to specify this parameter.
     * @return static the query object itself.
     * @see filterWhere()
     * @see orFilterWhere()
     */
    public function andFilterWhere(array $condition)
    {
        $condition = $this->filterCondition($condition);
        if ($condition !== []) {
            $this->andWhere($condition);
        }
        return $this;
    }

    /**
     * Adds an additional WHERE condition to the existing one but ignores [[isEmpty()|empty operands]].
     * The new condition and the existing one will be joined using the 'OR' operator.
     *
     * This method is similar to [[orWhere()]]. The main difference is that this method will
     * remove [[isEmpty()|empty query operands]]. As a result, this method is best suited
     * for building query conditions based on filter values entered by users.
     *
     * @param array $condition the new WHERE condition. Please refer to [[where()]]
     * on how to specify this parameter.
     * @return static the query object itself.
     * @see filterWhere()
     * @see andFilterWhere()
     */
    public function orFilterWhere(array $condition)
    {
        $condition = $this->filterCondition($condition);
        if ($condition !== []) {
            $this->orWhere($condition);
        }
        return $this;
    }

    /**
     * Removes [[isEmpty()|empty operands]] from the given query condition.
     *
     * @param array $condition the original condition
     * @return array the condition with [[isEmpty()|empty operands]] removed.
     * @throws Exception if the condition operator is not supported
     */
    protected function filterCondition($condition)
    {
        if (!is_array($condition)) {
            return $condition;
        }

        if (!isset($condition[0])) {
            // hash format: 'column1' => 'value1', 'column2' => 'value2', ...
            foreach ($condition as $name => $value) {
                if ($this->isEmpty($value)) {
                    unset($condition[$name]);
                }
            }
            return $condition;
        }

        // operator format: operator, operand 1, operand 2, ...

        $operator = array_shift($condition);

        switch (strtoupper($operator)) {
            case 'NOT':
            case 'AND':
            case 'OR':
                foreach ($condition as $i => $operand) {
                    $subCondition = $this->filterCondition($operand);
                    if ($this->isEmpty($subCondition)) {
                        unset($condition[$i]);
                    } else {
                        $condition[$i] = $subCondition;
                    }
                }

                if (empty($condition)) {
                    return [];
                }
                break;
            case 'IN':
            case 'NOT IN':
            case 'LIKE':
            case 'OR LIKE':
            case 'NOT LIKE':
            case 'OR NOT LIKE':
            case 'ILIKE': // PostgreSQL operator for case insensitive LIKE
            case 'OR ILIKE':
            case 'NOT ILIKE':
            case 'OR NOT ILIKE':
                if (array_key_exists(1, $condition) && $this->isEmpty($condition[1])) {
                    return [];
                }
                break;
            case 'BETWEEN':
            case 'NOT BETWEEN':
                if (array_key_exists(1, $condition) && array_key_exists(2, $condition)) {
                    if ($this->isEmpty($condition[1]) || $this->isEmpty($condition[2])) {
                        return [];
                    }
                }
                break;
            default:
                throw new Exception("Operator not supported: $operator");
        }

        array_unshift($condition, $operator);

        return $condition;
    }

    /**
     * Returns a value indicating whether the give value is "empty".
     *
     * The value is considered "empty", if one of the following conditions is satisfied:
     *
     * - it is `null`,
     * - an empty string (`''`),
     * - a string containing only whitespace characters,
     * - or an empty array.
     *
     * @param mixed $value
     * @return boolean if the value is empty
     */
    protected function isEmpty($value)
    {
        return $value === '' || $value === [] || $value === null || is_string($value) && trim($value) === '';
    }

    /**
     * Sets the ORDER BY part of the query.
     * @param string|array $columns the columns (and the directions) to be ordered by.
     * Columns can be specified in either a string (e.g. `"id ASC, name DESC"`) or an array
     * (e.g. `['id' => SORT_ASC, 'name' => SORT_DESC]`).
     * The method will automatically quote the column names unless a column contains some parenthesis
     * (which means the column contains a DB expression).
     * Note that if your order-by is an expression containing commas, you should always use an array
     * to represent the order-by information. Otherwise, the method will not be able to correctly determine
     * the order-by columns.
     * @return static the query object itself.
     * @see addOrderBy()
     */
    public function orderBy($columns)
    {
        $this->orderBy = $this->normalizeOrderBy($columns);
        return $this;
    }

    /**
     * Adds additional ORDER BY columns to the query.
     * @param string|array $columns the columns (and the directions) to be ordered by.
     * Columns can be specified in either a string (e.g. "id ASC, name DESC") or an array
     * (e.g. `['id' => SORT_ASC, 'name' => SORT_DESC]`).
     * The method will automatically quote the column names unless a column contains some parenthesis
     * (which means the column contains a DB expression).
     * @return static the query object itself.
     * @see orderBy()
     */
    public function addOrderBy($columns)
    {
        $columns = $this->normalizeOrderBy($columns);
        if ($this->orderBy === null) {
            $this->orderBy = $columns;
        } else {
            $this->orderBy = array_merge($this->orderBy, $columns);
        }
        return $this;
    }

    protected function normalizeOrderBy($columns)
    {
        if (is_array($columns)) {
            return $columns;
        } else {
            $columns = preg_split('/\s*,\s*/', trim($columns), -1, PREG_SPLIT_NO_EMPTY);
            $result = [];
            foreach ($columns as $column) {
                if (preg_match('/^(.*?)\s+(asc|desc)$/i', $column, $matches)) {
                    $result[$matches[1]] = strcasecmp($matches[2], 'desc') ? SORT_ASC : SORT_DESC;
                } else {
                    $result[$column] = SORT_ASC;
                }
            }
            return $result;
        }
    }

    /**
     * Sets the LIMIT part of the query.
     * @param integer $limit the limit. Use null or negative value to disable limit.
     * @return static the query object itself
     */
    public function limit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Sets the OFFSET part of the query.
     * @param integer $offset the offset. Use null or negative value to disable offset.
     * @return static the query object itself
     */
    public function offset($offset)
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * @param array      $rows
     * @param Connection $connection
     * @return array
     */
    public function typeCast($rows, Connection $connection = null)
    {
        if ($connection instanceof Connection) {
            $this->setConnection($connection);
        }
        $connection = $this->getConnection();
        if ($connection->typeCast) {
            $rows = is_array($rows) ? ArrayHelper::toType($rows) : Helper::toType($rows);
        }

        return $rows;
    }

    protected function removeAliasEntity($entity)
    {
        if (preg_match('/^(.*?)(?i:\s+as\s+|\s+)([\w\-_\.]+)$/', $entity, $matches)) {
            return $matches[1];
        } else {
            return $entity;
        }
    }
}
