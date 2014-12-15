<?php

namespace rock\sphinx;


use rock\db\ActiveRecordInterface;
use rock\helpers\ArrayHelper;
use rock\helpers\Helper;

/**
 * ActiveDataProvider implements a data provider based on {@see \rock\sphinx\Query} and {@see \rock\sphinx\ActiveQuery}.
 * ActiveDataProvider provides data by performing DB queries using {@see \rock\db\ActiveDataProvider::$query }.
 * And the following example shows how to use ActiveDataProvider without ActiveRecord:
 *
 * ```php
 * $config = [
 *      'query' => (new \rock\db\Query())->from('post'),
 *      'model' => PostIndex::className(),
 *      'callSnippets' => [
 *          'content' => [
 *              'about', // query search
 *              [
 *                  'limit' => 1000,
 *                  'before_match' => '<span>',
 *                  'after_match' => '</span>'
 *              ]
 *          ],
 *      ],
 *      'pagination' => ['limit' => 2, 'sort' => SORT_DESC]
 * ];
 * $provider = new \rock\sphinx\ActiveDataProvider($config);
 * $provider->get(); // get the posts in the current page
 * $provider->getPagination(); // get data pagination
 * ```
 *
 * The following is an example of using ActiveDataProvider to provide ActiveRecord instances:
 *
 * ```php
 * $provider = new \rock\sphinx\ActiveDataProvider([
 *     'query' => ArticleIndex::find()->match('about')->with('sourceCompositeLink'),
 *     'with' => 'sourceCompositeLink'
 *     'callSnippets' => [
 *          'content' => [
 *              'about', // query search
 *              [
 *                  'limit' => 1000,
 *                  'before_match' => '<span>',
 *                  'after_match' => '</span>'
 *              ]
 *          ],
 *          'title' => [
 *              'about',
 *              [
 *                  'limit' => 1000,
 *                  'before_match' => '<span>',
 *                  'after_match' => '</span>'
 *              ]
 *          ],
 *     ],
 *     'pagination' => ['limit' => 2, 'sort' => SORT_DESC]
 * ]);
 *
 * $provider->get(); // get the posts in the current page
 * $provider->getPagination(); // get data pagination
 */
class ActiveDataProvider extends \rock\db\ActiveDataProvider
{
    public $callSnippets = [];

    /** @var  string|\rock\sphinx\ActiveRecord */
    public $model;

    /** @var  string */
    public $with;

    protected function prepareArray()
    {
        if (!$query = parent::prepareArray()) {
            return [];
        }

        return $this->prepareResult($query);
    }

    protected function prepareModels(\rock\db\Connection $connection = null, $subAttributes = false)
    {
        if (!$query = parent::prepareModels($connection, $subAttributes)) {
            return [];
        }
        return $this->prepareResult($query);
    }

    protected function prepareResult($query)
    {
        reset($query);
        $firstElement = current($query);
        if ($firstElement instanceof ActiveRecordInterface) {
            if ($firstElement instanceof ActiveRecord) {
                $className = $firstElement;
                $this->model = $className::className();
            }
        }
        if ($this->query instanceof ActiveQuery) {
            $this->model = $this->query->modelClass;
        }
        if (!$this->model) {
            $this->model = ActiveRecord::className();
        }
        foreach ($this->callSnippets as $field => $value) {
            if (empty($value[0])) {
                continue;
            }
            $value[1] = Helper::getValue($value[1],[]);
            list($match, $options) = $value;

            if (isset($this->with)) {
                $query = $this->prepareAttributeWith($field, $match, $options, $query);
                continue;
            }

            $query = $this->prepareAttribute($field, $match, $options, $query);
        }

        return $query;
    }

    protected function prepareAttribute($field, $match, $options, $query)
    {
        if ($data = ArrayHelper::getColumn($query, $field)) {
            if ($data = array_combine(array_keys($data), call_user_func([$this->model, 'callSnippets'], $data, $match, $options))) {
                foreach ($query as $id => $attributes) {
                    if (isset($attributes[$field]) && isset($data[$id])) {
                        $query[$id][$field] = $data[$id];
                    }
                }
            }
        }

        return $query;
    }

    protected function prepareAttributeWith($field, $match, $options, $query)
    {
        if ($data = ArrayHelper::getColumn($query, function($element) use ($field){return $element[$this->with][$field];})) {
            if ($data = array_combine(array_keys($data), call_user_func([$this->model, 'callSnippets'], $data, $match, $options))) {
                foreach ($query as $id => $attributes) {
                    $attributes = $attributes[$this->with];
                    if (isset($attributes[$field]) && isset($data[$id])) {
                        $query[$id][$this->with][$field] = $data[$id];
                    }
                }
            }
        }
        return $query;
    }
}