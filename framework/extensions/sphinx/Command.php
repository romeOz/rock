<?php

namespace rock\sphinx;

/**
 * Command represents a SQL statement to be executed against a Sphinx.
 *
 * A command object is usually created by calling {@see \rock\sphinx\Connection::createCommand()}.
 * The SQL statement it represents can be set via the {@see \rock\db\Command::$_sql} property.
 *
 * To execute a non-query SQL (such as INSERT, REPLACE, DELETE, UPDATE), call {@see \rock\db\Command::execute()}.
 * To execute a SQL statement that returns result data set (such as SELECT, CALL SNIPPETS, CALL KEYWORDS),
 * use {@see \rock\db\Command::queryAll()}, {@see \rock\db\Command::queryOne()}, {@see \rock\db\Command::queryColumn()},
 * {@see \rock\db\Command::queryScalar()}, or {@see \rock\db\Command::query()}.
 * For example,
 *
 * ```php
 * $articles = $connection->createCommand("SELECT * FROM `idx_article` WHERE MATCH('programming')")->queryAll();
 * ```
 *
 * Command supports SQL statement preparation and parameter binding just as {@see \rock\db\Command} does.
 *
 * Command also supports building SQL statements by providing methods such as {@see \rock\db\Command::insert()},
 * {@see \rock\db\Command::update()}, etc. For example,
 *
 * ```php
 * $connection->createCommand()->update('idx_article', [
 *     'genre_id' => 15,
 *     'author_id' => 157,
 * ])->execute();
 * ```
 *
 * To build SELECT SQL statements, please use {@see \rock\sphinx\Query} and {@see \rock\sphinx\QueryBuilder} instead.
 */
class Command extends \rock\db\Command
{
    /**
     * @var Connection the Sphinx connection that this command is associated with.
     */
    public $connection;

    /**
     * Creates a batch INSERT command.
     * For example,
     *
     * ```php
     * $connection->createCommand()->batchInsert('idx_user', ['name', 'age'], [
     *     ['Tom', 30],
     *     ['Jane', 20],
     *     ['Linda', 25],
     * ])->execute();
     * ```
     *
     * Note that the values in each row must match the corresponding column names.
     *
     * @param string $index the index that new rows will be inserted into.
     * @param array $columns the column names
     * @param array $rows the rows to be batch inserted into the index
     * @return static the command object itself
     */
    public function batchInsert($index, $columns, $rows)
    {
        $params = [];
        $sql = $this->connection->getQueryBuilder()->batchInsert($index, $columns, $rows, $params);

        return $this->setSql($sql)->bindValues($params);
    }

    /**
     * Creates an REPLACE command.
     * For example,
     *
     * ```php
     * $connection->createCommand()->insert('idx_user', [
     *     'name' => 'Sam',
     *     'age' => 30,
     * ])->execute();
     * ```
     *
     * The method will properly escape the column names, and bind the values to be replaced.
     *
     * Note that the created command is not executed until {@see \rock\db\Command::execute()} is called.
     *
     * @param string $index the index that new rows will be replaced into.
     * @param array $columns the column data (name => value) to be replaced into the index.
     * @return static the command object itself
     */
    public function replace($index, $columns)
    {
        $params = [];
        $sql = $this->connection->getQueryBuilder()->replace($index, $columns, $params);

        return $this->setSql($sql)->bindValues($params);
    }

    /**
     * Creates a batch REPLACE command.
     * For example,
     *
     * ```php
     * $connection->createCommand()->batchInsert('idx_user', ['name', 'age'], [
     *     ['Tom', 30],
     *     ['Jane', 20],
     *     ['Linda', 25],
     * ])->execute();
     * ```
     *
     * Note that the values in each row must match the corresponding column names.
     *
     * @param string $index the index that new rows will be replaced.
     * @param array $columns the column names
     * @param array $rows the rows to be batch replaced in the index
     * @return static the command object itself
     */
    public function batchReplace($index, $columns, $rows)
    {
        $params = [];
        $sql = $this->connection->getQueryBuilder()->batchReplace($index, $columns, $rows, $params);

        return $this->setSql($sql)->bindValues($params);
    }

    /**
     * Creates an UPDATE command.
     * For example,
     *
     * ```php
     * $connection->createCommand()->update('user', ['status' => 1], 'age > 30')->execute();
     * ```
     *
     * The method will properly escape the column names and bind the values to be updated.
     *
     * Note that the created command is not executed until {@see \rock\db\Command::execute()} is called.
     *
     * @param string $index the index to be updated.
     * @param array $columns the column data (name => value) to be updated.
     * @param string|array $condition the condition that will be put in the WHERE part. Please
     * refer to {@see \rock\sphinx\Query::where()} on how to specify condition.
     * @param array $params the parameters to be bound to the command
     * @param array $options list of options in format: optionName => optionValue
     * @return static the command object itself
     */
    public function update($index, $columns, $condition = '', $params = [], $options = [])
    {
        $sql = $this->connection->getQueryBuilder()->update($index, $columns, $condition, $params, $options);

        return $this->setSql($sql)->bindValues($params);
    }

    /**
     * Creates a SQL command for truncating a runtime index.
     * @param string $index the index to be truncated. The name will be properly quoted by the method.
     * @return static the command object itself
     */
    public function truncateIndex($index)
    {
        $sql = $this->connection->getQueryBuilder()->truncateIndex($index);

        return $this->setSql($sql);
    }

    /**
     * Builds a snippet from provided data and query, using specified index settings.
     * @param string $index name of the index, from which to take the text processing settings.
     * @param string|array $source is the source data to extract a snippet from.
     * It could be either a single string or array of strings.
     * @param string $match the full-text query to build snippets for.
     * @param array $options list of options in format: optionName => optionValue
     * @return static the command object itself
     */
    public function callSnippets($index, $source, $match, $options = [])
    {
        $params = [];
        $sql = $this->connection->getQueryBuilder()->callSnippets($index, $source, $match, $options, $params);

        return $this->setSql($sql)->bindValues($params);
    }

    /**
     * Returns tokenized and normalized forms of the keywords, and, optionally, keyword statistics.
     * @param string $index the name of the index from which to take the text processing settings
     * @param string $text the text to break down to keywords.
     * @param boolean $fetchStatistic whether to return document and hit occurrence statistics
     * @return static the command object itself
     */
    public function callKeywords($index, $text, $fetchStatistic = false)
    {
        $params = [];
        $sql = $this->connection->getQueryBuilder()->callKeywords($index, $text, $fetchStatistic, $params);

        return $this->setSql($sql)->bindValues($params);
    }

    // Not Supported :

    /**
     * @inheritdoc
     */
    public function createTable($table, $columns, $options = null, $exists = false)
    {
        throw new Exception(Exception::UNKNOWN_METHOD, ['method' => __METHOD__]);
    }

    /**
     * @inheritdoc
     */
    public function renameTable($table, $newName)
    {
        throw new Exception(Exception::UNKNOWN_METHOD, ['method' => __METHOD__]);
    }

    /**
     * @inheritdoc
     */
    public function dropTable($table, $exists = false)
    {
        throw new Exception(Exception::UNKNOWN_METHOD, ['method' => __METHOD__]);
    }

    /**
     * @inheritdoc
     */
    public function truncateTable($table)
    {
        throw new Exception(Exception::UNKNOWN_METHOD, ['method' => __METHOD__]);
    }

    /**
     * @inheritdoc
     */
    public function addColumn($table, $column, $type)
    {
        throw new Exception(Exception::UNKNOWN_METHOD, ['method' => __METHOD__]);
    }

    /**
     * @inheritdoc
     */
    public function dropColumn($table, $column)
    {
        throw new Exception(Exception::UNKNOWN_METHOD, ['method' => __METHOD__]);
    }

    /**
     * @inheritdoc
     */
    public function renameColumn($table, $oldName, $newName)
    {
        throw new Exception(Exception::UNKNOWN_METHOD, ['method' => __METHOD__]);
    }

    /**
     * @inheritdoc
     */
    public function alterColumn($table, $column, $type)
    {
        throw new Exception(Exception::UNKNOWN_METHOD, ['method' => __METHOD__]);
    }

    /**
     * @inheritdoc
     */
    public function addPrimaryKey($name, $table, $columns)
    {
        throw new Exception(Exception::UNKNOWN_METHOD, ['method' => __METHOD__]);
    }

    /**
     * @inheritdoc
     */
    public function dropPrimaryKey($name, $table)
    {
        throw new Exception(Exception::UNKNOWN_METHOD, ['method' => __METHOD__]);
    }

    /**
     * @inheritdoc
     */
    public function addForeignKey($name, $table, $columns, $refTable, $refColumns, $delete = null, $update = null)
    {
        throw new Exception(Exception::UNKNOWN_METHOD, ['method' => __METHOD__]);
    }

    /**
     * @inheritdoc
     */
    public function dropForeignKey($name, $table)
    {
        throw new Exception(Exception::UNKNOWN_METHOD, ['method' => __METHOD__]);
    }

    /**
     * @inheritdoc
     */
    public function createIndex($name, $table, $columns, $unique = false)
    {
        throw new Exception(Exception::UNKNOWN_METHOD, ['method' => __METHOD__]);
    }

    /**
     * @inheritdoc
     */
    public function dropIndex($name, $table)
    {
        throw new Exception(Exception::UNKNOWN_METHOD, ['method' => __METHOD__]);
    }

    /**
     * @inheritdoc
     */
    public function resetSequence($table, $value = null)
    {
        throw new Exception(Exception::UNKNOWN_METHOD, ['method' => __METHOD__]);
    }

    /**
     * @inheritdoc
     */
    public function checkIntegrity($check = true, $schema = '', $table = '')
    {
        throw new Exception(Exception::UNKNOWN_METHOD, ['method' => __METHOD__]);
    }
}
