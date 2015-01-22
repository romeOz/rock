<?php

namespace apps\common\models\users\access;

use rock\db\ActiveRecord;
use rock\db\Connection;

abstract class BaseRoles extends ActiveRecord
{
    public static $connection;

    public static function getConnection()
    {
        if (static::$connection instanceof Connection) {
            return static::$connection;
        }
        return parent::getConnection();
    }

    public static function tableName()
    {
        return 'access_items roles';
    }
}