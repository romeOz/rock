<?php

namespace rockunit\core\db\mssql;

use rockunit\core\db\QueryTest;

/**
 * @group db
 * @group mssql
 */
class MssqlQueryTest extends QueryTest
{
    protected $driverName = 'sqlsrv';
}
