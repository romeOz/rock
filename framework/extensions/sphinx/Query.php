<?php
namespace rock\sphinx;

use rock\components\ModelEvent;
use rock\db\AfterFindEvent;
use rock\db\Expression;

/**
 * Query represents a SELECT SQL statement.
 *
 * Query provides a set of methods to facilitate the specification of different clauses
 * in a SELECT statement. These methods can be chained together.
 *
 * By calling {@see \rock\sphinx\Query::createCommand()}, we can get a {@see \rock\sphinx\Command} instance which can be further
 * used to perform/execute the Sphinx query.
 *
 * For example:
 *
 * ```php
 * $query = new \rock\sphinx\Query;
 * $query->select('id, group_id')
 *     ->from('idx_item')
 *     ->limit(10);
 * // build and execute the query
 * $command = $query->createCommand();
 * // $command->sql returns the actual SQL
 * $rows = $command->queryAll();
 * ```
 *
 * Since Sphinx does not store the original indexed text, the snippets for the rows in query result
 * should be build separately via another query. You can simplify this workflow using {@see \rock\sphinx\Query::$snippetCallback}.
 *
 * **Warning:** even if you do not set any query limit, implicit LIMIT 0,20 is present by default!
 */
class Query extends \rock\db\Query
{
    /**
     * @var string|Expression text, which should be searched in fulltext mode.
     * This value will be composed into MATCH operator inside the WHERE clause.
     * Note: this value will be processed by {@see \rock\sphinx\Connection::escapeMatchValue()},
     * if you need to compose complex match condition use {@see \rock\db\Expression},
     * see {@see \rock\sphinx\Query::match()} for details.
     */
    public $match;
    /**
     * @var string WITHIN GROUP ORDER BY clause. This is a Sphinx specific extension
     * that lets you control how the best row within a group will to be selected.
     * The possible value matches the {@see \rock\db\Query::$orderBy} one.
     */
    public $within;
    /**
     * @var array per-query options in format: optionName => optionValue
     * They will compose OPTION clause. This is a Sphinx specific extension
     * that lets you control a number of per-query options.
     */
    public $options;
    /**
     * @var callable PHP callback, which should be used to fetch source data for the snippets.
     * Such callback will receive array of query result rows as an argument and must return the
     * array of snippet source strings in the order, which match one of incoming rows.
     *
     * For example:
     *
     * ```php
     * $query = new Query;
     * $query->from('idx_item')
     *     ->match('pencil')
     *     ->snippetCallback(function ($rows) {
     *         $result = [];
     *         foreach ($rows as $row) {
     *             $result[] = file_get_contents('/path/to/index/files/' . $row['id'] . '.txt');
     *         }
     *         return $result;
     *     })
     *     ->all();
     * ```
     */
    public $snippetCallback;
    /**
     * @var array query options for the call snippet.
     */
    public $snippetOptions;

    /**
     * @var Connection|string
     */
    protected $connection = 'sphinx';

    /**
     * Creates a Sphinx command that can be used to execute this query.
     * @param Connection $connection the Sphinx connection used to generate the SQL statement.
     * If this parameter is not given, the `sphinx` application component will be used.
     * @return Command the created Sphinx command instance.
     */
    public function createCommand($connection = null)
    {
        if ($connection instanceof Connection) {
            $this->setConnection($connection);
        }
        $connection = $this->getConnection();
        $build = $connection->getQueryBuilder();
        $result = $build->build($this);
        list ($sql, $params) = $result;
        $entities = $build->entities;
        $command = $connection->createCommand($sql, $params);
        $command->entities = $entities;
        return $command;
    }
    /**
     * @inheritdoc
     */
    public function prepareResult($rows, $connection = null)
    {
        return parent::prepareResult($this->fillUpSnippets($rows), $connection);
    }

    /**
     * Executes the query and returns a single row of result.
     *
     * @param Connection $connection the Sphinx connection used to generate the SQL statement.
     * If this parameter is not given, the `sphinx` application component will be used.
     * @param boolean       $subAttributes
     * @return array|null the first row (in terms of an array) of the query result. False is returned if the query
     * results in nothing.
     */
    public function one($connection = null, $subAttributes = false)
    {
        $row = parent::one($connection, $subAttributes);
        if ($row !== null) {
            list ($row) = $this->fillUpSnippets([$row]);
        }

        return $row;
    }

    /**
     * Sets the fulltext query text. This text will be composed into
     * MATCH operator inside the WHERE clause.
     * Note: this value will be processed by {@see \rock\sphinx\Connection::escapeMatchValue()},
     * if you need to compose complex match condition use {@see \rock\db\Expression}:
     *
     * ```php
     * $query = new Query;
     * $query->from('my_index')
     *     ->match(new Expression(':match', ['match' => '@(content) ' . Rock::$app->sphinx->escapeMatchValue($matchValue)]))
     *     ->all();
     * ```
     *
     * @param string $query fulltext query text.
     * @return static the query object itself
     */
    public function match($query)
    {
        $this->match = $query;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function join($type, $table, $on = '', $params = [])
    {
        throw new SphinxException('"' . __METHOD__ . '" is not supported.');
    }

    /**
     * @inheritdoc
     */
    public function innerJoin($table, $on = '', $params = [])
    {
        throw new SphinxException('"' . __METHOD__ . '" is not supported.');
    }

    /**
     * @inheritdoc
     */
    public function leftJoin($table, $on = '', $params = [])
    {
        throw new SphinxException('"' . __METHOD__ . '" is not supported.');
    }

    /**
     * @inheritdoc
     */
    public function rightJoin($table, $on = '', $params = [])
    {
        throw new SphinxException('"' . __METHOD__ . '" is not supported.');
    }
    /**
     * Sets the query options.
     * @param array $options query options in format: optionName => optionValue
     * @return static the query object itself
     * @see addOptions()
     */
    public function options($options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Adds additional query options.
     * @param array $options query options in format: optionName => optionValue
     * @return static the query object itself
     * @see options()
     */
    public function addOptions($options)
    {
        if (is_array($this->options)) {
            $this->options = array_merge($this->options, $options);
        } else {
            $this->options = $options;
        }

        return $this;
    }

    /**
     * Sets the WITHIN GROUP ORDER BY part of the query.
     * @param string|array $columns the columns (and the directions) to find best row within a group.
     * Columns can be specified in either a string (e.g. "id ASC, name DESC") or an array
     * (e.g. `['id' => Query::SORT_ASC, 'name' => Query::SORT_DESC]`).
     * The method will automatically quote the column names unless a column contains some parenthesis
     * (which means the column contains a DB expression).
     * @return static the query object itself
     * @see addWithin()
     */
    public function within($columns)
    {
        $this->within = $this->normalizeOrderBy($columns);

        return $this;
    }

    /**
     * Adds additional WITHIN GROUP ORDER BY columns to the query.
     * @param string|array $columns the columns (and the directions) to find best row within a group.
     * Columns can be specified in either a string (e.g. "id ASC, name DESC") or an array
     * (e.g. `['id' => Query::SORT_ASC, 'name' => Query::SORT_DESC]`).
     * The method will automatically quote the column names unless a column contains some parenthesis
     * (which means the column contains a DB expression).
     * @return static the query object itself
     * @see within()
     */
    public function addWithin($columns)
    {
        $columns = $this->normalizeOrderBy($columns);
        if ($this->within === null) {
            $this->within = $columns;
        } else {
            $this->within = array_merge($this->within, $columns);
        }

        return $this;
    }

    /**
     * Sets the PHP callback, which should be used to retrieve the source data
     * for the snippets building.
     * @param callable $callback PHP callback, which should be used to fetch source data for the snippets.
     * @return static the query object itself
     * @see snippetCallback
     */
    public function snippetCallback($callback)
    {
        $this->snippetCallback = $callback;

        return $this;
    }

    /**
     * Sets the call snippets query options.
     * @param array $options call snippet options in format: option_name => option_value
     * @return static the query object itself
     * @see snippetCallback
     */
    public function snippetOptions($options)
    {
        $this->snippetOptions = $options;

        return $this;
    }

    /**
     * Fills the query result rows with the snippets built from source determined by
     * {@see \rock\sphinx\Query::$snippetCallback} result.
     * @param array $rows raw query result rows.
     * @return array|ActiveRecord[] query result rows with filled up snippets.
     */
    protected function fillUpSnippets($rows)
    {
        if ($this->snippetCallback === null) {
            return $rows;
        }
        $snippetSources = call_user_func($this->snippetCallback, $rows);
        $snippets = $this->callSnippets($snippetSources);
        $snippetKey = 0;
        foreach ($rows as $key => $row) {
            $rows[$key]['snippet'] = $snippets[$snippetKey];
            $snippetKey++;
        }

        return $rows;
    }

    /**
     * Builds a snippets from provided source data.
     *
*@param array $source the source data to extract a snippet from.
     * @throws SphinxException in case {@see \rock\sphinx\Query::$match} is not specified.
     * @return array snippets list.
     */
    protected function callSnippets(array $source)
    {
        return $this->callSnippetsInternal($source, $this->from[0]);
    }

    /**
     * Builds a snippets from provided source data by the given index.
     *
*@param array $source the source data to extract a snippet from.
     * @param string $from name of the source index.
     * @return array snippets list.
     * @throws SphinxException in case {@see \rock\sphinx\Query::$match} is not specified.
     */
    protected function callSnippetsInternal(array $source, $from)
    {
        $connection = $this->getConnection();
        $match = $this->match;
        if ($match === null) {
            throw new SphinxException('Unable to call snippets: "' . $this->className() . '::match" should be specified.');
        }

        return $connection->createCommand()
            ->callSnippets($from, $source, $match, $this->snippetOptions)
            ->queryColumn();
    }


    /**
     * @inheritdoc
     */
    protected function queryScalar($selectExpression, $connection)
    {
        $select = $this->select;
        $limit = $this->limit;
        $offset = $this->offset;

        $this->select = [$selectExpression];
        $this->limit = null;
        $this->offset = null;
        $command = $this->createCommand($connection);

        $this->select = $select;
        $this->limit = $limit;
        $this->offset = $offset;

        if (empty($this->groupBy) && empty($this->union) && !$this->distinct) {
            return $command->queryScalar();
        } else {
            return (new Query)->select([$selectExpression])
                ->from(['c' => $this])
                ->createCommand($command->connection)
                ->queryScalar();
        }
    }

    /**
     * Creates a new Query object and copies its property values from an existing one.
     * The properties being copies are the ones to be used by query builders.
     * @param Query $from the source query object
     * @return Query the new Query object
     */
    public static function create($from)
    {
        return new self([
            'where' => $from->where,
            'limit' => $from->limit,
            'offset' => $from->offset,
            'orderBy' => $from->orderBy,
            'indexBy' => $from->indexBy,
            'select' => $from->select,
            'selectOption' => $from->selectOption,
            'distinct' => $from->distinct,
            'from' => $from->from,
            'groupBy' => $from->groupBy,
            'join' => $from->join,
            'having' => $from->having,
            'union' => $from->union,
            'params' => $from->params,
            // Sphinx specifics :
            'options' => $from->options,
            'within' => $from->within,
            'match' => $from->match,
            'snippetCallback' => $from->snippetCallback,
            'snippetOptions' => $from->snippetOptions,
        ]);
    }
    public function getRawSql($connection = null)
    {
        if ($connection instanceof Connection) {
            $this->setConnection($connection);
        }
        $connection = $this->getConnection();

        list ($sql, $params) = $connection->getQueryBuilder()->build($this);
        return $connection->createCommand($sql, $params)->getRawSql();
    }

    /**
     * This method is called when the AR object is created and populated with the query result.
     *
     * The default implementation will trigger an {@see BaseActiveRecord::EVENT_BEFORE_FIND} event.
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
     * This method is called when the AR object is created and populated with the query result.
     *
     * The default implementation will trigger an {@see BaseActiveRecord::EVENT_AFTER_FIND} event.
     * When overriding this method, make sure you call the parent implementation to ensure the
     * event is triggered.
     *
     * @param mixed $result the query result.
     */
    public function afterFind(&$result = null)
    {
        $event = new AfterFindEvent();
        $event->result = $result;
        $this->trigger(self::EVENT_AFTER_FIND, $event);
        $result = $event->result;
    }
}
