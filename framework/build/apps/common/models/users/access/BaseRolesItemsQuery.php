<?php

namespace apps\common\models\users\access;


use rock\db\ActiveQuery;

abstract class BaseRolesItemsQuery extends ActiveQuery
{
    public static function tableName()
    {
        return RolesItems::tableName();
    }

    /**
     * @param $roleId
     * @return static
     */
    public function byRole($roleId)
    {
        return $this->andWhere([static::tableName() . '.role' =>  $roleId]);
    }

    /**
     * @param $itemId
     * @return static
     */
    public function byItem($itemId)
    {
        return $this->andWhere([static::tableName() . '.item' =>  $itemId]);
    }

    /**
     * @param array $itemIds
     * @return static
     */
    public function byItems(array $itemIds)
    {
        return $this->andWhere([static::tableName() . '.item' => $itemIds]);
    }
} 