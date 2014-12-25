<?php

namespace rockunit\extensions\sphinx\models;

/**
 * Test Sphinx ActiveRecord class
 */
class ActiveRecord extends \rock\sphinx\ActiveRecord
{
    public static $db;

    public static function getConnection()
    {
        return self::$db;
    }
}
