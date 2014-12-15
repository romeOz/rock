<?php

namespace rockunit\core\db\mssql;

use rockunit\core\db\ActiveRecordTest;

/**
 * @group db
 * @group mssql
 */
class MssqlActiveRecordTest extends ActiveRecordTest
{
    protected $driverName = 'sqlsrv';
}
