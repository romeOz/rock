<?php
namespace rockunit\core\db\sqlite;

use rock\db\Expression;
use rock\db\Query;
use rockunit\core\db\QueryTest;

/**
 * @group db
 * @group sqlite
 */
class SqliteQueryTest extends QueryTest
{
    protected $driverName = 'sqlite';

    public function testUnion()
    {
        $connection = $this->getConnection();
        $query = new Query;
        $query->select(['id', 'name', new Expression("'item' ". $this->replaceQuotes('`tbl`'))])
            ->from('item')
            ->union(
                (new Query())
                    ->select(['id', 'name', new Expression("'category' ". $this->replaceQuotes('`tbl`'))])
                    ->from(['category'])
            );
        $sql = $this->replaceQuotes("SELECT `id`, `name`, 'item' `tbl` FROM `item` UNION  SELECT `id`, `name`, 'category' `tbl` FROM `category`");
        $this->assertSame($query->getRawSql($connection), $sql);
        $result = $query->all($connection);
        $this->assertNotEmpty($result);
        $this->assertSame(7, count($result));

        $query = new Query;
        $query->select(['id', 'name', new Expression("'item' ". $this->replaceQuotes('`tbl`'))])
            ->from('item')
            ->union(
                (new Query())
                    ->select(['id', 'name', new Expression("'category' ". $this->replaceQuotes('`tbl`'))])
                    ->from(['category'])
            )
            //->unionOrderBy(['item' => SORT_DESC]);
            ->unionLimit(3);
        $sql =$this->replaceQuotes("SELECT `id`, `name`, 'item' `tbl` FROM `item` UNION  SELECT `id`, `name`, 'category' `tbl` FROM `category` LIMIT 3");
        $this->assertSame($query->getRawSql($connection), $sql);
        $result = $query->all($connection);
        $this->assertSame(3, count($result));
    }
}
