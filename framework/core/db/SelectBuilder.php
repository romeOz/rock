<?php

namespace rock\db;


use rock\base\ObjectTrait;
use rock\di\Container;
use rock\helpers\Helper;

/**
 * Builder SELECT
 *
 * @method static SelectBuilder selects(array $selects)
 * @method static SelectBuilder select($fields, $alias = false, string $separator = null)
 *
 * ```php
 * SelectBuilder::selects([Articles::find()->fields()]);
 * //sql: SELECT `articles`.`id`, `articles`.`name`
 *
 * SelectBuilder::select(Articles::find()->fields(), true, '__');
 * //sql: SELECT `articles`.`id` AS `articles__id`, `articles`.`name` AS `articles__id`
 * ```
 */
class SelectBuilder
{
    use ObjectTrait {
        ObjectTrait::__call as parentCall;
    }

    /** @var string|Connection  */
    public $connection = 'db';

    /**
     * @var array
     */
    public $selects = [];

    public static function __callStatic($name, $arguments)
    {
        /** @var static $self */
        $self = Container::load(static::className());
        return call_user_func_array([$self, $name], $arguments);
    }

    /**
     * @inheritdoc
     */
    public function __call($name, $arguments)
    {
        $method = "{$name}Internal";
        if (!method_exists($this, $method)) {
            $this->parentCall($name, $arguments);
        }
        return call_user_func_array([$this, "{$name}Internal"], $arguments);
    }

    /**
     * @param array      $params
     * @throws Exception
     * @return array
     */
    public function build(&$params = [])
    {
        /** @var Connection $connection */
        $connection = is_string($this->connection) ? Container::load($this->connection) : $this->connection;
        $result = [];
        foreach ($this->selects as $key => $select) {

            $alias = false;
            $aliasSeparator = null;
            if (is_array($select) && !is_string($key) && !is_string(key($select))) {
                $select[1] = Helper::getValueIsset($select[1], false);
                $select[2] = Helper::getValueIsset($select[2]);

                list($select, $alias, $aliasSeparator) = $select;
            }

            if ($select instanceof ActiveQuery) {
                if (!isset($select->modelClass)) {
                    continue;
                }
                /** @var ActiveRecord $class */
                $class = $select->modelClass;
                $table = $class::tableAlias() ? : $class::tableName();
                $tableAlias = $table;
                if (is_string($alias)) {
                    $tableAlias = $alias;
                    $alias = true;
                }
                $connection = $class::getDb();

                if (!$columns = $select->select) {
                    continue;
                }
            } elseif(is_array($select)) {

                if (!is_string($key)) {
                    $table = key($select);
                    $select[0] = Helper::getValueIsset($select[0], false);
                    $select[1] = Helper::getValueIsset($select[1]);
                    list($alias, $aliasSeparator) = $select;
                    $columns = current($select);
                } else {
                    $table = $key;
                    $columns = $select;
                }

                $tableAlias = $table;
                if (is_string($alias)) {
                    $tableAlias = $alias;
                    $alias = true;
                }
            } else {
                throw new Exception(Exception::WRONG_TYPE, ['name' => json_encode($select)]);
            }
            $aliasSeparator = Helper::getValue($aliasSeparator, $connection->aliasSeparator);

            foreach ($columns as $i => $column) {
                if ($column instanceof Expression) {
                    $columns[$i] = $column->expression;
                    $params = array_merge($params, $column->params);
                } elseif (is_string($i)) {
                    if (strpos($column, '(') === false) {
                        $column = $connection->quoteColumnName($column);
                    }
                    $columns[$i] = "$column AS " . $connection->quoteColumnName($i);
                } elseif (strpos($column, '(') === false) {
                    if (preg_match('/^(.*?)(?i:\s+as\s+|\s+)([\w\-_\.]+)$/', $column, $matches)) {
                        $matches[2] = $alias === true ? $connection->quoteColumnName($tableAlias . $aliasSeparator . $matches[2]) : $connection->quoteColumnName($matches[2]);
                        $columns[$i]
                            = $connection->quoteTableName($table) .'.'. $connection->quoteColumnName($matches[1]) . ' AS ' . $matches[2];
                    } else {
                        $columns[$i] = $connection->quoteTableName($table) .'.'. $connection->quoteColumnName($column) . ($alias === true ? ' AS ' . $connection->quoteColumnName($tableAlias . $aliasSeparator . $column) : null);
                    }
                } elseif (stripos($column, 'CONCAT') !== false) {
                    if (preg_match('/^(.*?)(?i:\s+as\s+|\s+)([\w\-_\.]+)$/', $column, $matches)) {
                        $matches[2] = $alias === true ? $connection->quoteColumnName($tableAlias . $aliasSeparator . $matches[2]) : $connection->quoteColumnName($matches[2]);
                        $columns[$i] = $connection->quoteColumnName($matches[1]) . ' AS ' . $matches[2];
                    } else {
                        $columns[$i] = $connection->quoteTableName($table) .'.'. $connection->quoteColumnName($column) . ($alias === true ? ' AS ' . $connection->quoteColumnName($tableAlias . $aliasSeparator . $column) : null);
                    }
                }
            }

            $result = array_merge($result, $columns);
        }

        return implode(', ', $result);
    }

    protected function selectInternal($fields, $alias = false, $separator = null)
    {
        $this->selects[] = is_array($fields) && is_string(key($fields))
            ? [key($fields) => current($fields), $alias, $separator]
            : [$fields, $alias, $separator];
        return $this;
    }

    /**
     * Adds selects.
     *
     * ```php
     * SelectBuilder::selects([Articles::find()->fields()]);
     * //sql: SELECT `articles`.`id`, `articles`.`name`
     *
     * SelectBuilder::selects(['articles'=>['id', 'name']]);
     * //sql: SELECT `articles`.`id`, `articles`.`name`
     *
     * SelectBuilder::selects([[Articles::find()->fields(), true, '__']]);
     * //sql: SELECT `articles`.`id` AS `articles__id`, `articles`.`name` AS `articles__id`
     *
     * SelectBuilder::selects([['articles'=>['id', 'name'], true, '__']]);
     * //sql: SELECT `articles`.`id` AS `articles__id`, `articles`.`name` AS `articles__id`
     * ```
     *
     * @param array $selects
     * @return $this
     */
    protected function selectsInternal(array $selects)
    {
        $this->selects = $selects;
        return $this;
    }
} 