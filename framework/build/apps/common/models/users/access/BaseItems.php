<?php

namespace apps\common\models\users\access;


use rock\db\ActiveQuery;
use rock\db\ActiveRecord;
use rock\db\Connection;
use rock\Rock;

abstract class BaseItems extends ActiveRecord
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
        return 'access_items';
    }
}