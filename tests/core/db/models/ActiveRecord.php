<?php
namespace rockunit\core\db\models;


class ActiveRecord extends \rock\db\ActiveRecord
{
    public static $db;

    public static function getConnection()
    {
        return self::$db;
    }
}
