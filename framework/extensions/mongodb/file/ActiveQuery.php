<?php
namespace rock\mongodb\file;

use rock\db\ActiveQueryInterface;
use rock\db\ActiveQueryTrait;
use rock\db\ActiveRelationTrait;

/**
 * ActiveQuery represents a Mongo query associated with an file Active Record class.
 *
 * ActiveQuery instances are usually created by {@see \rock\db\ActiveRecordInterface::find()}.
 *
 * Because ActiveQuery extends from {@see \rock\mongodb\Query}, one can use query methods, such as {@see \rock\db\QueryInterface::where()},
 * {@see \rock\db\QueryInterface::orderBy()} to customize the query options.
 *
 * ActiveQuery also provides the following additional query options:
 *
 * - {@see \rock\db\ActiveQueryInterface::with()}: list of relations that this query should be performed with.
 * - {@see \rock\db\ActiveQueryInterface::asArray()}: whether to return each record as an array.
 *
 * These options can be configured using methods of the same name. For example:
 *
 * ```php
 * $images = ImageFile::find()->with('tags')->asArray()->all();
 * ```
 *
 * @property Collection $collection Collection instance. This property is read-only.
 *
 */
class ActiveQuery extends Query implements ActiveQueryInterface
{
    use ActiveQueryTrait;
    use ActiveRelationTrait;

    /**
     * @event Event an event that is triggered when the query is initialized via {@see \rock\base\ObjectInterface::init()}.
     */
    const EVENT_INIT = 'init';


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
     * Initializes the object.
     * This method is called at the end of the constructor. The default implementation will trigger
     * an {@see \rock\mongodb\file\ActiveQuery::EVENT_INIT} event. If you override this method, make sure you call the parent implementation at the end
     * to ensure triggering of the event.
     */
    public function init()
    {
        parent::init();
        $this->trigger(self::EVENT_INIT);
    }

    /**
     * @inheritdoc
     */
    protected function buildCursor($connection = null)
    {
        if ($this->primaryModel !== null) {
            // lazy loading
            if ($this->via instanceof self) {
                // via pivot collection
                $viaModels = $this->via->findPivotRows([$this->primaryModel]);
                $this->filterByModels($viaModels);
            } elseif (is_array($this->via)) {
                // via relation
                /* @var $viaQuery ActiveQuery */
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

        return parent::buildCursor($connection);
    }

    /**
     * Executes query and returns all results as an array.
     *
     * @param \rock\mongodb\Connection $connection the Mongo connection used to execute the query.
     * If null, the Mongo connection returned by {@see \rock\db\ActiveQueryTrait::$modelClass} will be used.
     * @return array the query results. If the query results in nothing, an empty array will be returned.
     */
    public function all($connection = null)
    {        // before
        /** @var ActiveRecord $class */
        $class = $this->modelClass;
        $activeRecord = $class::instantiate([]);
        if (!$activeRecord->beforeFind()) {
            return [];
        }

        $cursor = $this->buildCursor($connection);
        $rows = $this->fetchRows($cursor);
        if (!empty($rows)) {
            $models = $this->createModels($rows);
            if (!empty($this->with)) {
                $this->findWith($this->with, $models);
            }
            if (!$this->asArray) {
                foreach ($models as $model) {
                    $model->afterFind();
                }
            }

            return $models;
        } else {
            return [];
        }
    }

    /**
     * Executes query and returns a single row of result.
     *
     * @param \rock\mongodb\Connection $connection the Mongo connection used to execute the query.
     * If null, the Mongo connection returned by {@see \rock\db\ActiveQueryTrait::$modelClass} will be used.
     * @return ActiveRecord|array|null a single row of query result. Depending on the setting of {@see \rock\db\ActiveQueryTrait::$asArray},
     * the query result may be either an array or an ActiveRecord object. Null will be returned
     * if the query results in nothing.
     */
    public function one($connection = null)
    {
        // before
        /** @var ActiveRecord $class */
        $class = $this->modelClass;
        /** @var ActiveRecord $model */
        $model  = $class::instantiate([]);
        if (!$model->beforeFind()) {
            return null;
        }

        $row = parent::one($connection);
        if ($row !== null) {
            if ($this->asArray) {
                $model = $row;
            } else {
//                /* @var $class ActiveRecord */
//                $class = $this->modelClass;
//                $model = $class::instantiate($row);
                $class::populateRecord($model, $row);
            }
            if (!empty($this->with)) {
                $models = [$model];
                $this->findWith($this->with, $models);
                $model = $models[0];
            }
            if (!$this->asArray) {
                $model->afterFind();
            }

            return $model;
        } else {
            return null;
        }
    }

    /**
     * Returns the Mongo collection for this query.
     *
     * @param \rock\mongodb\Connection $connection Mongo connection.
     * @return Collection collection instance.
     */
    public function getCollection($connection = null)
    {
        /* @var $modelClass ActiveRecord */
        $modelClass = $this->modelClass;
        if ($connection === null) {
            $connection = $modelClass::getConnection();
        }
        $this->connection = $connection;
        if ($this->from === null) {
            $this->from = $modelClass::collectionName();
        }
        $this->calculateCacheParams($this->connection);
        return $this->connection->getFileCollection($this->from);
    }
}
