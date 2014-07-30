<?php

namespace apps\common\models\users\access;


use rock\db\ActiveQuery;
use rock\db\ActiveRecord;
use rock\db\Connection;
use string;

abstract class BaseRolesItems extends ActiveRecord
{
    public static $connection;

    public static function getDb()
    {
        if (static::$connection instanceof Connection) {
            return static::$connection;
        }
        return parent::getDb();
    }

    public static function tableName()
    {
        return 'access_roles_items';
    }


    public function getItems()
    {
        return $this->hasOne(Items::className(), ['name' => 'item']);
    }

    public function getRoles()
    {
        /** @noinspection PhpUndefinedMethodInspection */
        return $this->hasOne(Roles::className(), ['name' => 'role'])->isRole()->sortByMenuIndex();
    }
} 