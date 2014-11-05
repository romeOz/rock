<?php

namespace rockunit\core\behaviors;

use rockunit\core\db\DatabaseTestCase;
use rockunit\core\db\models\ActiveRecord;
use rockunit\core\db\models\Order;
use rockunit\core\db\models\OrderTimestamp;

/**
 * @group base
 * @group db
 */
class TimestampBehaviorTest extends DatabaseTestCase
{
    protected function setUp()
    {
        parent::setUp();
        ActiveRecord::$db = $this->getConnection();
    }

    public function testInsert()
    {
        $query= new Order();
        $query->customer_id = 2;
        $query->total = 77;
        $this->assertNull($query->created_at);
        $this->assertTrue($query->save());
        $this->assertNotEmpty($query->created_at);
        $this->assertSame($query->created_at, $query::findOne($query->getPrimaryKey())->created_at);
        //$this->assertTrue((bool)Articles::deleteAll(['id' => $query->getPrimaryKey()]));

        $query= new OrderTimestamp();
        $query->customer_id = 2;
        $query->total = 77;
        $this->assertNull($query->created_at);
        $this->assertTrue($query->save());
        $this->assertNotEmpty($query->created_at);
        $this->assertSame($query->created_at, $query::findOne($query->getPrimaryKey())->created_at);
    }

    public function testUpdate()
    {
        $query = Order::findOne(2);
        $created_at = $query->created_at;
        $query->total = 55;
        $this->assertTrue($query->save());
        $this->assertNotEmpty($query->created_at);
        $this->assertNotEquals($created_at, $query->created_at);
        $this->assertSame($query->created_at, $query::findOne($query->getPrimaryKey())->created_at);
    }
}
 