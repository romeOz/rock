<?php

namespace rock\db;


use rock\base\ClassName;
use rock\helpers\Helper;

/**
 * Class SelectBuilder
 * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 * new SelectBuilder([Articles::find()->fields()])
 * sql: SELECT `articles`.`id`, `articles`.`name`
 *
 * new SelectBuilder(['articles'=>['id', 'name']])
 * sql: SELECT `articles`.`id`, `articles`.`name`
 *
 * new SelectBuilder([[Articles::find()->fields(), true, '__']])
 * sql: SELECT `articles`.`id` AS `articles__id`, `articles`.`name` AS `articles__id`
 *
 * new SelectBuilder([['articles'=>['id', 'name'], true, '__']])
 * sql: SELECT `articles`.`id` AS `articles__id`, `articles`.`name` AS `articles__id`
 * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 *
 * @package rock\db
 */
class SelectBuilder
{
    use ClassName;

    /**
     * @var array
     */
    private $_selects = [];

    public function __construct(array $selects)
    {
        $this->_selects = $selects;
    }


    /**
     *
     * @param Connection $db
     * @param array      $params
     * @throws Exception
     * @return array
     */
    public function build(Connection $db, &$params = [])
    {
        $result = [];
        foreach ($this->_selects as $key =>  $select) {

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
                $db = $class::getDb();

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
                throw new Exception(Exception::CRITICAL, Exception::WRONG_TYPE, ['name' => json_encode($select)]);
            }
            $aliasSeparator = Helper::getValue($aliasSeparator, $db->aliasSeparator);

            foreach ($columns as $i => $column) {

                if ($column instanceof Expression) {
                    $columns[$i] = $column->expression;
                    $params = array_merge($params, $column->params);
                } elseif (is_string($i)) {
                    if (strpos($column, '(') === false) {
                        $column = $db->quoteColumnName($column);
                    }
                    $columns[$i] = "$column AS " . $db->quoteColumnName($i);
                } elseif (strpos($column, '(') === false) {
                    if (preg_match('/^(.*?)(?i:\s+as\s+|\s+)([\w\-_\.]+)$/', $column, $matches)) {
                        $matches[2] = $alias === true ? $db->quoteColumnName($tableAlias . $aliasSeparator . $matches[2]) : $db->quoteColumnName($matches[2]);
                        $columns[$i]
                            = $db->quoteTableName($table) .'.'. $db->quoteColumnName($matches[1]) . ' AS ' . $matches[2];
                    } else {
                        $columns[$i] = $db->quoteTableName($table) .'.'. $db->quoteColumnName($column) . ($alias === true ? ' AS ' . $db->quoteColumnName($tableAlias . $aliasSeparator . $column) : null);
                    }
                } elseif (stripos($column, 'CONCAT') !== false) {
                    if (preg_match('/^(.*?)(?i:\s+as\s+|\s+)([\w\-_\.]+)$/', $column, $matches)) {
                        $matches[2] = $alias === true ? $db->quoteColumnName($tableAlias . $aliasSeparator . $matches[2]) : $db->quoteColumnName($matches[2]);
                        $columns[$i] = $db->quoteColumnName($matches[1]) . ' AS ' . $matches[2];
                    } else {
                        $columns[$i] = $db->quoteTableName($table) .'.'. $db->quoteColumnName($column) . ($alias === true ? ' AS ' . $db->quoteColumnName($tableAlias . $aliasSeparator . $column) : null);
                    }
                }
            }

            $result = array_merge($result, $columns);
        }

        return implode(', ', $result);
    }
} 