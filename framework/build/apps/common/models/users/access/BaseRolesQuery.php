<?php

namespace apps\common\models\users\access;


use rock\db\ActiveQuery;
use rock\rbac\RBACInterface;


class BaseRolesQuery extends ActiveQuery
{
    public static function tableName()
    {
        return Roles::tableAlias();
    }

    /** Fields */
    public function fields()
    {
        return $this->isRole()->select(['name', 'type', 'description', 'data']);
    }

    /** where is */
    /**
     * @return static
     */
    public function isRole()
    {
        return $this->andWhere([static::tableName() . '.type' => RBACInterface::TYPE_ROLE]);
    }

    /** order by */
    public function sortByMenuIndex()
    {
        return $this->orderBy([static::tableName() . '.order_index' => SORT_DESC]);
    }
} 