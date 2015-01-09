<?php

namespace rockunit\extensions\mongodb\models;

/**
 * Test Mongo ActiveRecord
 */
class ActiveRecord extends \rock\mongodb\ActiveRecord
{
    public static $connection;

    public static function getConnection()
    {
        return self::$connection;
    }

}
