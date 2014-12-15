<?php
namespace rockunit\core\db\pgsql;

use rockunit\core\db\ActiveDataProviderTest;

/**
 * @group db
 * @group pgsql
 * @group data
 */
class PostgreSQLActiveDataProviderTest extends ActiveDataProviderTest
{
    public $driverName = 'pgsql';
}
