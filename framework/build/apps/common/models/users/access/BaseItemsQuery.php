<?php

namespace apps\common\models\users\access;


use rock\db\ActiveQuery;


abstract class BaseItemsQuery extends ActiveQuery
{
    public static function tableName()
    {
        return Items::tableName();
    }

    /** Fields */
    public function fields()
    {
        return $this->select(['name', 'type', 'description', 'data']);
    }

    /** where by */
    public function byItem($item)
    {
        return $this->andWhere([static::tableName() . '.name' => $item]);
    }

    /**
     * @param array $itemsIds
     * @return static
     */
    public function byItems(array $itemsIds)
    {
        return $this->andWhere([static::tableName() . '.name' => $itemsIds]);
    }

    /** order by */
    /**
     * @return static
     */
    public function sortByMenuIndex()
    {
        return $this->orderBy([static::tableName() . '.menuindex' => SORT_DESC]);
    }
} 