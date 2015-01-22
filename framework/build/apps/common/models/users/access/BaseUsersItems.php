<?php

namespace apps\common\models\users\access;

use rock\db\ActiveRecord;
use rock\db\Connection;

class BaseUsersItems extends ActiveRecord
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
        return 'access_users_items';
    }

    /**
     * @inheritdoc
     * @return UsersItemsQuery
     */
    public static function find()
    {
        return new UsersItemsQuery(get_called_class());
    }
} 