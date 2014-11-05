<?php

namespace rock\sphinx;

use rock\db\ActiveQueryInterface;
use rock\db\ActiveQueryTrait;
use rock\db\ActiveRelationTrait;
use rock\event\Event;

/**
 * ActiveQuery represents a Sphinx query associated with an Active Record class.
 *
 * An ActiveQuery can be a normal query or be used in a relational context.
 *
 * ActiveQuery instances are usually created by [[ActiveRecord::find()]] and [[ActiveRecord::findBySql()]].
 * Relational queries are created by [[ActiveRecord::hasOne()]] and [[ActiveRecord::hasMany()]].
 *
 * Normal Query
 * ------------
 *
 * Because ActiveQuery extends from [[Query]], one can use query methods, such as [[where()]],
 * [[orderBy()]] to customize the query options.
 *
 * ActiveQuery also provides the following additional query options:
 *
 * - [[with()]]: list of relations that this query should be performed with.
 * - [[indexBy()]]: the name of the column by which the query result should be indexed.
 * - [[asArray()]]: whether to return each record as an array.
 *
 * These options can be configured using methods of the same name. For example:
 *
 * ~~~
 * $articles = Article::find()->with('source')->asArray()->all();
 * ~~~
 *
 * ActiveQuery allows to build the snippets using sources provided by ActiveRecord.
 * You can use [[snippetByModel()]] method to enable this.
 * For example:
 *
 * ```php
 * class Article extends ActiveRecord
 * {
 *     public function getSource()
 *     {
 *         return $this->hasOne('db', ArticleDb::className(), ['id' => 'id']);
 *     }
 *
 *     public function getSnippetSource()
 *     {
 *         return $this->source->content;
 *     }
 *
 *     ...
 * }
 *
 * $articles = Article::find()->with('source')->snippetByModel()->all();
 * ```
 *
 * Relational query
 * ----------------
 *
 * In relational context ActiveQuery represents a relation between two Active Record classes.
 *
 * Relational ActiveQuery instances are usually created by calling [[ActiveRecord::hasOne()]] and
 * [[ActiveRecord::hasMany()]]. An Active Record class declares a relation by defining
 * a getter method which calls one of the above methods and returns the created ActiveQuery object.
 *
 * A relation is specified by [[link]] which represents the association between columns
 * of different tables; and the multiplicity of the relation is indicated by [[multiple]].
 *
 * If a relation involves a pivot table, it may be specified by [[via()]].
 * This methods may only be called in a relational context. Same is true for [[inverseOf()]], which
 * marks a relation as inverse of another relation.
 */
class ActiveQuery extends Query implements ActiveQueryInterface
{
    use ActiveQueryTrait;
    use ActiveRelationTrait;

    /**
     * @var string the SQL statement to be executed for retrieving AR records.
     * This is set by [[ActiveRecord::findBySql()]].
     */
    public $sql;


    /**
     * Constructor.
     * @param array $modelClass the model class associated with this query
     * @param array $config configurations to be applied to the newly created query object
     */
    public function __construct($modelClass, $config = [])
    {
        $this->modelClass = $modelClass;
        parent::__construct($config);
    }

    /**
     * Sets the [[snippetCallback]] to [[fetchSnippetSourceFromModels()]], which allows to
     * fetch the snippet source strings from the Active Record models, using method
     * [[ActiveRecord::getSnippetSource()]].
     * For example:
     *
     * ```php
     * class Article extends ActiveRecord
     * {
     *     public function getSnippetSource()
     *     {
     *         return file_get_contents('/path/to/source/files/' . $this->id . '.txt');;
     *     }
     * }
     *
     * $articles = Article::find()->snippetByModel()->all();
     * ```
     *
     * Warning: this option should NOT be used with [[asArray]] at the same time!
     * @return static the query object itself
     */
    public function snippetByModel()
    {
        $this->snippetCallback([$this, 'fetchSnippetSourceFromModels']);

        return $this;
    }

    /**
     * Executes query and returns all results as an array.
     *
     * @param Connection $connection the DB connection used to create the DB command.
     * @param boolean       $subAttributes
     * If null, the DB connection returned by [[modelClass]] will be used.
     * @return array|ActiveRecord[] the query results. If the query results in nothing, an empty array will be returned.
     */
    public function all($connection = null, $subAttributes = false)
    {
        if (!$this->beforeFind()) {
            return [];
        }
        /** @var ActiveRecord $class */
        $class = $this->modelClass;
        $activeRecord = $class::instantiate([]);
        if (!$activeRecord->beforeFind()) {
            return [];
        }
        $command = $this->createCommand($connection);
        $rows = $command->queryAll(null, $subAttributes);

        if (!empty($rows)) {
            $models = $this->createModels($rows);
            if (!empty($this->with)) {
                if (isset($this->queryBuild->entities)) {
                    $this->queryBuild->entities = [];
                }
                $this->findWith($this->with, $models);
            }
            $models = $this->fillUpSnippets($models);
            if (!$this->asArray) {
//                if (!$this->afterFind($models)) {
//                    return [];
//                }
                foreach ($models as $model) {
                    $model->afterFind();
                }
                Event::offMulti(static::$_events);
            } else {
                $this->afterFind($models);
                $activeRecord->afterFind($models);
            }

            return $models;
        } else {
            return [];
        }
    }

    /**
     * Executes query and returns a single row of result.
     *
     * @param Connection $connection the DB connection used to create the DB command.
     * If null, the DB connection returned by [[modelClass]] will be used.
     * @param boolean       $subAttributes
     * @return ActiveRecord|array|null a single row of query result. Depending on the setting of [[asArray]],
     * the query result may be either an array or an ActiveRecord object. Null will be returned
     * if the query results in nothing.
     */
    public function one($connection = null, $subAttributes = false)
    {
        /** @var ActiveRecord $class */
        $class = $this->modelClass;
        /** @var ActiveRecord $model */
        $model = $activeRecord = $class::instantiate([]);

        if (!$this->beforeFind()) {
            return null;
        }
        if (!$activeRecord->beforeFind()) {
            return null;
        }
        $command = $this->createCommand($connection);
        $row = $command->queryOne(null, $subAttributes);

        if ($row !== false) {
            if ($this->asArray) {
                $model = $this->typeCast($row, $connection);
                //$model = $this->typeCast($row, $class::getIndexSchema($connection)->columns);
            } else {
                $class::populateRecord($model, $row, $connection);
            }
            if (!empty($this->with)) {
                if (isset($this->queryBuild->entities)) {
                    $this->queryBuild->entities = [];
                }
                $models = [$model];
                $this->findWith($this->with, $models);
                $model = $models[0];
            }
            list ($model) = $this->fillUpSnippets([$model]);
            $this->afterFind($model);
            $activeRecord->afterFind($model);
            return $model;
        } else {
            return null;
        }
    }

    /** @var  QueryBuilder */
    private $queryBuild;

    /**
     * Creates a DB command that can be used to execute this query.
     *
     * @param Connection $connection the DB connection used to create the DB command.
     * If null, the DB connection returned by [[modelClass]] will be used.
     * @return Command the created DB command instance.
     */
    public function createCommand($connection = null)
    {
        if ($this->primaryModel !== null) {
            // lazy loading a relational query
            if ($this->via instanceof self) {
                // via pivot index
                $viaModels = $this->via->findPivotRows([$this->primaryModel]);
                $this->filterByModels($viaModels);
            } elseif (is_array($this->via)) {
                // via relation
                /** @var ActiveQuery $viaQuery */
                list($viaName, $viaQuery) = $this->via;
                if ($viaQuery->multiple) {
                    $viaModels = $viaQuery->all();
                    $this->primaryModel->populateRelation($viaName, $viaModels);
                } else {
                    $model = $viaQuery->one();
                    $this->primaryModel->populateRelation($viaName, $model);
                    $viaModels = $model === null ? [] : [$model];
                }
                $this->filterByModels($viaModels);
            } else {
                $this->filterByModels([$this->primaryModel]);
            }
        }

        if ($connection instanceof Connection) {
            $this->setConnection($connection);
        }
        $connection = $this->getConnection();

        $entities = [];
        if ($this->sql === null) {
            $build =  $connection->getQueryBuilder();
            $result = $build->build($this);
            $entities = $build->entities;
            $this->queryBuild = $build;
            list ($sql, $params) = $result;
        } else {
            $sql = $this->sql;
            $params = $this->params;
        }
        $command = $connection->createCommand($sql, $params);
        $command->entities = $entities;

        return $command;
    }

    /**
     * @return Connection
     */
    public function getConnection()
    {
        if ($this->connection instanceof Connection) {
            return $this->calculateCacheParams($this->connection);
        }
        /** @var ActiveRecord $modelClass */
        $modelClass = $this->modelClass;
        $this->connection = $modelClass::getDb();
        return $this->calculateCacheParams($this->connection);
    }


    /**
     * Fetches the source for the snippets using [[ActiveRecord::getSnippetSource()]] method.
     * @param ActiveRecord[] $models raw query result rows.
     * @throws Exception if [[asArray]] enabled.
     * @return array snippet source strings
     */
    protected function fetchSnippetSourceFromModels($models)
    {
        if ($this->asArray) {
            throw new Exception('"' . __METHOD__ . '" unable to determine snippet source from plain array. Either disable "asArray" option or use regular "snippetCallback"');
        }
        $result = [];
        foreach ($models as $model) {
            $result[] = $model->getSnippetSource();
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    protected function callSnippets(array $source)
    {
        $from = $this->from;
        if ($from === null) {
            /** @var ActiveRecord $modelClass */
            $modelClass = $this->modelClass;
            $tableName = $modelClass::indexName();
            $from = [$tableName];
        }

        return $this->callSnippetsInternal($source, $from[0]);
    }
}
