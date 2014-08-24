<?php

namespace apps\common\models\users\access;


class Roles extends BaseRoles
{
    /**
     * @inheritdoc
     * @return RolesQuery
     */
    public static function find()
    {
        return new RolesQuery(get_called_class());
    }
} 