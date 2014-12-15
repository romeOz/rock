<?php
namespace rockunit\core\db\mssql;

use rockunit\core\db\ActiveDataProviderTest;

/**
 * @group db
 * @group mssql
 * @group data
 */
class MssqlActiveDataProviderTest extends ActiveDataProviderTest
{
    public $driverName = 'sqlsrv';
}
