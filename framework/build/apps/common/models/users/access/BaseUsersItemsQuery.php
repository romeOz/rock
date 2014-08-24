<?php

namespace apps\common\models\users\access;


use rock\db\ActiveQuery;


abstract class BaseUsersItemsQuery extends ActiveQuery
{
    public static function tableName()
    {
        return UsersItems::tableName();
    }



    public function byUserId($id)
    {
        return $this->andWhere([static::tableName() . '.user_id' =>  $id]);
    }

    public function byUserIds($ids)
    {
        return $this->andWhere([static::tableName() . '.user_id' => $ids]);
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