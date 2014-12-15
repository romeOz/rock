<?php
namespace rockunit\core\db\sqlite;

use rockunit\core\db\ActiveDataProviderTest;

/**
 * @group db
 * @group sqlite
 * @group data
 */
class SqliteActiveDataProviderTest extends ActiveDataProviderTest
{
    public $driverName = 'sqlite';
}
