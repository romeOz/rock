<?php

namespace rockunit\core\db\pgsql;

use rock\db\Query;
use rockunit\core\db\QueryTest;

/**
 * @group db
 * @group pgsql
 */
class PostgreSQLQueryTest extends QueryTest
{
    public $driverName = 'pgsql';

    public function testBooleanValues()
    {
        $db = $this->getConnection();
        $command = $db->createCommand();
        $command->batchInsert('bool_values',
            ['bool_col'], [
                [true],
                [false],
            ]
        )->execute();

        $this->assertEquals(1, (new Query())->from('bool_values')->where('bool_col = TRUE')->count('*', $db));
        $this->assertEquals(1, (new Query())->from('bool_values')->where('bool_col = FALSE')->count('*', $db));
        $this->assertEquals(2, (new Query())->from('bool_values')->where('bool_col IN (TRUE, FALSE)')->count('*', $db));

        $this->assertEquals(1, (new Query())->from('bool_values')->where(['bool_col' => true])->count('*', $db));
        $this->assertEquals(1, (new Query())->from('bool_values')->where(['bool_col' => false])->count('*', $db));
        $this->assertEquals(2, (new Query())->from('bool_values')->where(['bool_col' => [true, false]])->count('*', $db));

        $this->assertEquals(1, (new Query())->from('bool_values')->where('bool_col = :bool_col', ['bool_col' => true])->count('*', $db));
        $this->assertEquals(1, (new Query())->from('bool_values')->where('bool_col = :bool_col', ['bool_col' => false])->count('*', $db));
    }
}
