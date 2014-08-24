<?php

namespace apps\common\models\users\access;


class RolesItems extends BaseRolesItems
{
    /**
     * @inheritdoc
     * @return RolesItemsQuery
     */
    public static function find()
    {
        return new RolesItemsQuery(get_called_class());
    }
} 