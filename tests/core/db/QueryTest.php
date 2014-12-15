<?php

namespace rockunit\core\db;

use rock\access\Access;
use rock\db\Expression;
use rock\db\Query;
use rock\db\SelectBuilder;
use rock\event\Event;
use rock\helpers\Trace;
use rockunit\common\CommonTrait;

/**
 * @group db
 * @group mysql
 */
class QueryTest extends DatabaseTestCase
{
    use CommonTrait;

    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();
        static::getCache()->flush();
        static::disableCache();
        static::clearRuntime();
    }

    public function testSelect()
    {
        // default
        $query = new Query;
        $query->select('*');
        $this->assertEquals(['*'], $query->select);
        $this->assertNull($query->distinct);
        $this->assertEquals(null, $query->selectOption);

        $query = new Query;
        $query->select('id, name', 'something')->distinct(true);
        $this->assertEquals(['id', 'name'], $query->select);
        $this->assertTrue($query->distinct);
        $this->assertEquals('something', $query->selectOption);

        $query = new Query();
        $query->addSelect('email');
        $this->assertEquals(['email'], $query->select);

        $query = new Query();
        $query->select('id, name');
        $query->addSelect('email');
        $this->assertEquals(['id', 'name', 'email'], $query->select);
    }

    public function testFrom()
    {
        $query = new Query;
        $query->from('user');
        $this->assertEquals(['user'], $query->from);
    }

    public function testWhere()
    {
        $query = new Query;
        $query->where('id = :id', [':id' => 1]);
        $this->assertEquals('id = :id', $query->where);
        $this->assertEquals([':id' => 1], $query->params);

        $query->andWhere('name = :name', [':name' => 'something']);
        $this->assertEquals(['and', 'id = :id', 'name = :name'], $query->where);
        $this->assertEquals([':id' => 1, ':name' => 'something'], $query->params);

        $query->orWhere('age = :age', [':age' => '30']);
        $this->assertEquals(['or', ['and', 'id = :id', 'name = :name'], 'age = :age'], $query->where);
        $this->assertEquals([':id' => 1, ':name' => 'something', ':age' => '30'], $query->params);
    }

    public function testFilterWhere()
    {
        // should work with hash format
        $query = new Query;
        $query->filterWhere([
            'id' => 0,
            'title' => '   ',
            'author_ids' => [],
        ]);
        $this->assertEquals(['id' => 0], $query->where);

        $query->andFilterWhere(['status' => null]);
        $this->assertEquals(['id' => 0], $query->where);

        $query->orFilterWhere(['name' => '']);
        $this->assertEquals(['id' => 0], $query->where);

        // should work with operator format
        $query = new Query;
        $condition = ['like', 'name', 'Alex'];
        $query->filterWhere($condition);
        $this->assertEquals($condition, $query->where);

        $query->andFilterWhere(['between', 'id', null, null]);
        $this->assertEquals($condition, $query->where);

        $query->orFilterWhere(['not between', 'id', null, null]);
        $this->assertEquals($condition, $query->where);

        $query->andFilterWhere(['in', 'id', []]);
        $this->assertEquals($condition, $query->where);

        $query->andFilterWhere(['not in', 'id', []]);
        $this->assertEquals($condition, $query->where);

        $query->andFilterWhere(['not in', 'id', []]);
        $this->assertEquals($condition, $query->where);

        $query->andFilterWhere(['like', 'id', '']);
        $this->assertEquals($condition, $query->where);

        $query->andFilterWhere(['or like', 'id', '']);
        $this->assertEquals($condition, $query->where);

        $query->andFilterWhere(['not like', 'id', '   ']);
        $this->assertEquals($condition, $query->where);

        $query->andFilterWhere(['or not like', 'id', null]);
        $this->assertEquals($condition, $query->where);
    }

    public function testFilterRecursively()
    {
        $query = new Query();
        $query->filterWhere(['and', ['like', 'name', ''], ['like', 'title', ''], ['id' => 1], ['not', ['like', 'name', '']]]);
        $this->assertEquals(['and', ['id' => 1]], $query->where);
    }

    public function testJoin()
    {
    }

    public function testGroup()
    {
        $query = new Query;
        $query->groupBy('team');
        $this->assertEquals(['team'], $query->groupBy);

        $query->addGroupBy('company');
        $this->assertEquals(['team', 'company'], $query->groupBy);

        $query->addGroupBy('age');
        $this->assertEquals(['team', 'company', 'age'], $query->groupBy);
    }

    public function testHaving()
    {
        $query = new Query;
        $query->having('id = :id', [':id' => 1]);
        $this->assertEquals('id = :id', $query->having);
        $this->assertEquals([':id' => 1], $query->params);

        $query->andHaving('name = :name', [':name' => 'something']);
        $this->assertEquals(['and', 'id = :id', 'name = :name'], $query->having);
        $this->assertEquals([':id' => 1, ':name' => 'something'], $query->params);

        $query->orHaving('age = :age', [':age' => '30']);
        $this->assertEquals(['or', ['and', 'id = :id', 'name = :name'], 'age = :age'], $query->having);
        $this->assertEquals([':id' => 1, ':name' => 'something', ':age' => '30'], $query->params);
    }

    public function testOrder()
    {
        $query = new Query;
        $query->orderBy('team');
        $this->assertEquals(['team' => SORT_ASC], $query->orderBy);

        $query->addOrderBy('company');
        $this->assertEquals(['team' => SORT_ASC, 'company' => SORT_ASC], $query->orderBy);

        $query->addOrderBy('age');
        $this->assertEquals(['team' => SORT_ASC, 'company' => SORT_ASC, 'age' => SORT_ASC], $query->orderBy);

        $query->addOrderBy(['age' => SORT_DESC]);
        $this->assertEquals(['team' => SORT_ASC, 'company' => SORT_ASC, 'age' => SORT_DESC], $query->orderBy);

        $query->addOrderBy('age ASC, company DESC');
        $this->assertEquals(['team' => SORT_ASC, 'company' => SORT_DESC, 'age' => SORT_ASC], $query->orderBy);
    }

    public function testLimitOffset()
    {
        $query = new Query;
        $query->limit(10)->offset(5);
        $this->assertEquals(10, $query->limit);
        $this->assertEquals(5, $query->offset);
    }

    public function testUnion()
    {
        $connection = $this->getConnection();
        $query = new Query;
        $query->select(['id', 'name', new Expression("'item' ". $this->replaceQuotes('`tbl`'))])
            ->from('item')
            ->limit(2)
            ->union(
                (new Query())
                    ->select(['id', 'name', new Expression("'category' ". $this->replaceQuotes('`tbl`'))])
                    ->from(['category'])
                    ->limit(2)
            );
        $sql = $this->replaceQuotes("(SELECT `id`, `name`, 'item' `tbl` FROM `item` LIMIT 2) UNION ( SELECT `id`, `name`, 'category' `tbl` FROM `category` LIMIT 2 )");
        $this->assertSame($query->getRawSql($connection), $sql);
        $result = $query->all($connection);
        $this->assertNotEmpty($result);
        $this->assertSame(4, count($result));

        $query = new Query;
        $query->select(['id', 'name', new Expression("'item' ". $this->replaceQuotes('`tbl`'))])
            ->from('item')
            ->limit(2)
            ->union(
                (new Query())
                    ->select(['id', 'name', new Expression("'category' ". $this->replaceQuotes('`tbl`'))])
                    ->from(['category'])
                    ->limit(2)
            )
            ->unionOrderBy(['item' => SORT_DESC])
            ->unionLimit(3);
        $sql =$this->replaceQuotes("(SELECT `id`, `name`, 'item' `tbl` FROM `item` LIMIT 2) UNION ( SELECT `id`, `name`, 'category' `tbl` FROM `category` LIMIT 2 ) ORDER BY `item` DESC LIMIT 3");
        $this->assertSame($query->getRawSql($connection), $sql);
    }

    public function testIndexBy()
    {
        $connection = $this->getConnection();
        $result = (new Query)->from('customer')->indexBy('name')->all($connection);
        $this->assertSame(key($result), 'user1');
    }

    public function testOne()
    {
        $connection = $this->getConnection();
        $result = (new Query)->from('customer')->where(['status' => 2])->one($connection);
        $this->assertEquals('user3', $result['name']);
        $result = (new Query)->from('customer')->where(['status' => 3])->one($connection);
        $this->assertFalse($result);
    }

    public function testSelectBuilder()
    {
        $connection = $this->getConnection();
        $query = new Query();
        $query->select(
            [
                new SelectBuilder(
                    [
                        ['customer' => ['id', 'name'], true],
                        ['order' => ['id', 'total'], 'orders', '+']
                    ])
            ]
        )
            ->from(['customer'])
            ->innerJoin('order', 'customer.id=order.customer_id');
        $sql = $this->replaceQuotes(
            "SELECT `customer`.`id` AS `customer__id`, `customer`.`name` AS `customer__name`, `order`.`id` AS `orders+id`, `order`.`total` AS `orders+total` FROM `customer` INNER JOIN `order` ON customer.id=order.customer_id");
        $this->assertSame($query->getRawSql($connection), $sql);
    }

    public function testCache()
    {
        $cache = static::getCache();
        $cache->flush();

        $connection = $this->getConnection();
        Trace::removeAll();
        $connection->enableQueryCache = true;
        $connection->queryCache = $cache;
        $query = new Query();
        $query->select(
            [
                new SelectBuilder(
                    [
                        ['customer' => ['id', 'name']],
                        ['order' => ['id', 'total'], 'orders']
                    ])
            ]
        )
            ->from(['customer'])
            ->innerJoin('order', '{{customer}}.{{id}}={{order}}.{{customer_id}}');
        $result = $query->one($connection, true);
        $this->assertSame($result['name'], 'user1');
        $this->assertFalse(Trace::getIterator('db.query')->current()['cache']);
        $this->assertNotEmpty($query->one($connection));
        $this->assertTrue(Trace::getIterator('db.query')->current()['cache']);
        $this->assertNotEmpty($query->endCache()->one($connection));
        $this->assertFalse(Trace::getIterator('db.query')->current()['cache']);
        $this->assertSame(Trace::getIterator('db.query')->current()['count'], 3);
        $cache->flush();
        Trace::removeAll();

        // beginCache and EndCache
        $connection->queryCache = 'cache';
        $connection->enableQueryCache = false;
        $query = (new Query())->setConnection($connection)->from(['customer']);
        $this->assertNotEmpty($query->all());
        $this->assertFalse(Trace::getIterator('db.query')->current()['cache']);
        $this->assertNotEmpty($query->all());
        $this->assertFalse(Trace::getIterator('db.query')->current()['cache']);
        $cache->flush();
        Trace::removeAll();
        $this->assertNotEmpty($query->beginCache()->all());
        $this->assertFalse(Trace::getIterator('db.query')->current()['cache']);
        $this->assertNotEmpty($query->beginCache()->all());
        $this->assertTrue(Trace::getIterator('db.query')->current()['cache']);
        $this->assertNotEmpty($query->beginCache()->endCache()->all());
        $this->assertFalse(Trace::getIterator('db.query')->current()['cache']);
        static::disableCache();
    }


    public function testCheckAccessFail()
    {
        $connection = $this->getConnection();
        $query = new Query();
        $query->from('customer');
        $query->checkAccess(
            [
                'allow' => true,
                'verbs' => ['POST'],
            ],
            [
                function (Access $access) {
                    $this->assertTrue($access->owner instanceof Query);
                    echo 'success';
                }
            ],
            [
                function (Access $access) {
                    $this->assertTrue($access->owner instanceof Query);
                    echo 'fail';
                }
            ]
        );
        $this->assertEmpty($query->one($connection));
        $this->assertEmpty(Event::getAll());
        $this->assertEmpty($query->all($connection));
        $this->expectOutputString('failfail');
    }

    public function testCheckAccessSuccess()
    {
        $connection = $this->getConnection();
        $query = new Query();
        $query->from('customer');
        $query->checkAccess(
            [
                'allow' => true,
                'verbs' => ['GET'],
            ],
            function (Access $access) {
                $this->assertTrue($access->owner instanceof Query);
                echo 'success';
            }
            ,
            [
                function (Access $access) {
                    $this->assertTrue($access->owner instanceof Query);
                    echo 'fail';
                }
            ]
        );
        $this->assertNotEmpty($query->one($connection));
        $this->assertEmpty(Event::getAll());
        $this->assertNotEmpty($query->all($connection));
        $this->assertEmpty(Event::getAll());
        $this->expectOutputString('successsuccess');
    }
}
