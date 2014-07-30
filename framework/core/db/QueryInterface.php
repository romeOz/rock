<?php
namespace rock\db;

use rock\base\ComponentsInterface;

/**
 * The QueryInterface defines the minimum set of methods to be implemented by a database query.
 *
 * The default implementation of this interface is provided by [[QueryTrait]].
 *
 * It has support for getting [[one]] instance or [[all]].
 * Allows pagination via [[limit]] and [[offset]].
 * Sorting is supported via [[orderBy]] and items can be limited to match some conditions using [[where]].
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Carsten Brandt <mail@cebe.cc>
 * @since 2.0
 */
interface QueryInterface extends ComponentsInterface
{
    /**
     * Sets the SELECT part of the query.
     * @param string|array|SelectBuilder $columns the columns to be selected.
     * Columns can be specified in either a string (e.g. "id, name") or an array (e.g. ['id', 'name']).
     * Columns can be prefixed with table names (e.g. "user.id") and/or contain column aliases (e.g. "user.id AS user_id").
     * The method will automatically quote the column names unless a column contains some parenthesis
     * (which means the column contains a DB expression).
     *
     * Note that if you are selecting an expression like `CONCAT(first_name, ' ', last_name)`, you should
     * use an array to specify the columns. Otherwise, the expression may be incorrectly split into several parts.
     *
     * When the columns are specified as an array, you may also use array keys as the column aliases (if a column
     * does not need alias, do not use a string key).
     *
     * @param string $option additional option that should be appended to the 'SELECT' keyword. For example,
     * in MySQL, the option 'SQL_CALC_FOUND_ROWS' can be used.
     * @return static the query object itself
     */
    public function select($columns, $option = null);

    /**
     * Executes the query and returns all results as an array.
     *
     * @param Connection $connection the database connection used to execute the query.
     * If this parameter is not given, the `db` application component will be used.
     * @param boolean       $subAttributes
     * @return array the query results. If the query results in nothing, an empty array will be returned.
     */
    public function all($connection = null, $subAttributes = false);

    /**
     * Executes the query and returns a single row of result.
     *
     * @param Connection $connection the database connection used to execute the query.
     * If this parameter is not given, the `db` application component will be used.
     * @param boolean       $subAttributes
     * @return array|boolean the first row (in terms of an array) of the query result. False is returned if the query
     * results in nothing.
     */
    public function one($connection = null, $subAttributes = false);

    /**
     * Returns the query result as a scalar value.
     * The value returned will be the first column in the first row of the query results.
     *
     * @param Connection $connection the database connection used to generate the SQL statement.
     * If this parameter is not given, the `db` application component will be used.
     * @return string|boolean the value of the first column in the first row of the query result.
     * False is returned if the query result is empty.
     */
    public function scalar($connection = null);

    /**
     * Returns the number of records.
     *
     * @param string $q the COUNT expression. Defaults to '*'.
     * @param Connection $connection the database connection used to execute the query.
     * If this parameter is not given, the `db` application component will be used.
     * @return integer number of records
     */
    public function count($q = '*', $connection = null);

    /**
     * Returns a value indicating whether the query result contains any row of data.
     *
     * @param Connection $connection the database connection used to execute the query.
     * If this parameter is not given, the `db` application component will be used.
     * @return boolean whether the query result contains any row of data.
     */
    public function exists($connection = null);

    /**
     * Executes the query and returns the first column of the result.
     *
     * @param Connection $connection the database connection used to generate the SQL statement.
     * If this parameter is not given, the `db` application component will be used.
     * @return array the first column of the query result. An empty array is returned if the query results in nothing.
     */
    public function column($connection = null);

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
    public function indexBy($column);

    /**
     * Sets the WHERE part of the query.
     *
     * The method requires a $condition parameter.
     *
     * The $condition parameter should be an array in one of the following two formats:
     *
     * - hash format: `['column1' => value1, 'column2' => value2, ...]`
     * - operator format: `[operator, operand1, operand2, ...]`
     *
     * A condition in hash format represents the following SQL expression in general:
     * `column1=value1 AND column2=value2 AND ...`. In case when a value is an array,
     * an `IN` expression will be generated. And if a value is null, `IS NULL` will be used
     * in the generated expression. Below are some examples:
     *
     * - `['type' => 1, 'status' => 2]` generates `(type = 1) AND (status = 2)`.
     * - `['id' => [1, 2, 3], 'status' => 2]` generates `(id IN (1, 2, 3)) AND (status = 2)`.
     * - `['status' => null] generates `status IS NULL`.
     *
     * A condition in operator format generates the SQL expression according to the specified operator, which
     * can be one of the followings:
     *
     * - `and`: the operands should be concatenated together using `AND`. For example,
     *   `['and', 'id=1', 'id=2']` will generate `id=1 AND id=2`. If an operand is an array,
     *   it will be converted into a string using the rules described here. For example,
     *   `['and', 'type=1', ['or', 'id=1', 'id=2']]` will generate `type=1 AND (id=1 OR id=2)`.
     *   The method will NOT do any quoting or escaping.
     *
     * - `or`: similar to the `and` operator except that the operands are concatenated using `OR`.
     *
     * - `between`: operand 1 should be the column name, and operand 2 and 3 should be the
     *   starting and ending values of the range that the column is in.
     *   For example, `['between', 'id', 1, 10]` will generate `id BETWEEN 1 AND 10`.
     *
     * - `not between`: similar to `between` except the `BETWEEN` is replaced with `NOT BETWEEN`
     *   in the generated condition.
     *
     * - `in`: operand 1 should be a column or DB expression, and operand 2 be an array representing
     *   the range of the values that the column or DB expression should be in. For example,
     *   `['in', 'id', [1, 2, 3]]` will generate `id IN (1, 2, 3)`.
     *   The method will properly quote the column name and escape values in the range.
     *
     * - `not in`: similar to the `in` operator except that `IN` is replaced with `NOT IN` in the generated condition.
     *
     * - `like`: operand 1 should be a column or DB expression, and operand 2 be a string or an array representing
     *   the values that the column or DB expression should be like.
     *   For example, `['like', 'name', 'tester']` will generate `name LIKE '%tester%'`.
     *   When the value range is given as an array, multiple `LIKE` predicates will be generated and concatenated
     *   using `AND`. For example, `['like', 'name', ['test', 'sample']]` will generate
     *   `name LIKE '%test%' AND name LIKE '%sample%'`.
     *   The method will properly quote the column name and escape special characters in the values.
     *   Sometimes, you may want to add the percentage characters to the matching value by yourself, you may supply
     *   a third operand `false` to do so. For example, `['like', 'name', '%tester', false]` will generate `name LIKE '%tester'`.
     *
     * - `or like`: similar to the `like` operator except that `OR` is used to concatenate the `LIKE`
     *   predicates when operand 2 is an array.
     *
     * - `not like`: similar to the `like` operator except that `LIKE` is replaced with `NOT LIKE`
     *   in the generated condition.
     *
     * - `or not like`: similar to the `not like` operator except that `OR` is used to concatenate
     *   the `NOT LIKE` predicates.
     *
     * @param string|array $condition the conditions that should be put in the WHERE part.
     * @return static the query object itself
     * @see andWhere()
     * @see orWhere()
     */
    public function where($condition);

    /**
     * Adds an additional WHERE condition to the existing one.
     * The new condition and the existing one will be joined using the 'AND' operator.
     * @param string|array $condition the new WHERE condition. Please refer to [[where()]]
     * on how to specify this parameter.
     * @return static the query object itself
     * @see where()
     * @see orWhere()
     */
    public function andWhere($condition);

    /**
     * Adds an additional WHERE condition to the existing one.
     * The new condition and the existing one will be joined using the 'OR' operator.
     * @param string|array $condition the new WHERE condition. Please refer to [[where()]]
     * on how to specify this parameter.
     * @return static the query object itself
     * @see where()
     * @see andWhere()
     */
    public function orWhere($condition);

    /**
     * Sets the WHERE part of the query ignoring empty parameters.
     *
     * @param array $condition the conditions that should be put in the WHERE part. Please refer to [[where()]]
     * on how to specify this parameter.
     * @return static the query object itself
     * @see andFilterWhere()
     * @see orFilterWhere()
     */
    public function filterWhere(array $condition);

    /**
     * Adds an additional WHERE condition to the existing one ignoring empty parameters.
     * The new condition and the existing one will be joined using the 'AND' operator.
     * @param array $condition the new WHERE condition. Please refer to [[where()]]
     * on how to specify this parameter.
     * @return static the query object itself
     * @see filterWhere()
     * @see orFilterWhere()
     */
    public function andFilterWhere(array $condition);

    /**
     * Adds an additional WHERE condition to the existing one ignoring empty parameters.
     * The new condition and the existing one will be joined using the 'OR' operator.
     * @param array $condition the new WHERE condition. Please refer to [[where()]]
     * on how to specify this parameter.
     * @return static the query object itself
     * @see filterWhere()
     * @see andFilterWhere()
     */
    public function orFilterWhere(array $condition);

    /**
     * Sets the ORDER BY part of the query.
     * @param string|array $columns the columns (and the directions) to be ordered by.
     * Columns can be specified in either a string (e.g. "id ASC, name DESC") or an array
     * (e.g. `['id' => SORT_ASC, 'name' => SORT_DESC]`).
     * The method will automatically quote the column names unless a column contains some parenthesis
     * (which means the column contains a DB expression).
     * @return static the query object itself
     * @see addOrderBy()
     */
    public function orderBy($columns);

    /**
     * Adds additional ORDER BY columns to the query.
     * @param string|array $columns the columns (and the directions) to be ordered by.
     * Columns can be specified in either a string (e.g. "id ASC, name DESC") or an array
     * (e.g. `['id' => SORT_ASC, 'name' => SORT_DESC]`).
     * The method will automatically quote the column names unless a column contains some parenthesis
     * (which means the column contains a DB expression).
     * @return static the query object itself
     * @see orderBy()
     */
    public function addOrderBy($columns);

    /**
     * Sets the LIMIT part of the query.
     * @param integer $limit the limit. Use null or negative value to disable limit.
     * @return static the query object itself
     */
    public function limit($limit);

    /**
     * Sets the OFFSET part of the query.
     * @param integer $offset the offset. Use null or negative value to disable offset.
     * @return static the query object itself
     */
    public function offset($offset);


    /**
     * @param int  $expire
     * @param string[]|null $tags
     * @return static
     */
    public function beginCache($expire = 0, array $tags = null);

    /**
     * @return static
     */
    public function endCache();

    public function getRawSql($connection = null);
}
