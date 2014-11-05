<?php

namespace rockunit\core\db;


use rock\access\Access;
use rock\db\ActiveQuery;
use rock\db\ActiveRecordInterface;
use rock\event\Event;
use rock\helpers\Trace;
use rock\Rock;
use rockunit\common\CommonTrait;
use rockunit\core\db\models\ActiveRecord;
use rockunit\core\db\models\Customer;
use rockunit\core\db\models\CustomerFilter;
use rockunit\core\db\models\CustomerRules;
use rockunit\core\db\models\Item;
use rockunit\core\db\models\Order;
use rockunit\core\db\models\OrderItem;
use rockunit\core\db\models\OrderItemWithNullFK;
use rockunit\core\db\models\OrderWithNullFK;

/**
 * @group db
 * @group mysql
 */
class ActiveRecordTest extends DatabaseTestCase
{
    use CommonTrait;
    use ActiveRecordTestTrait;

    public function getCustomerClass()
    {
        return Customer::className();
    }

    public function getCustomerRulesClass()
    {
        return CustomerRules::className();
    }

    public function getCustomerFilterClass()
    {
        return CustomerFilter::className();
    }

    public function getItemClass()
    {
        return Item::className();
    }

    public function getOrderClass()
    {
        return Order::className();
    }

    public function getOrderItemClass()
    {
        return OrderItem::className();
    }

    public function getOrderWithNullFKClass()
    {
        return OrderWithNullFK::className();
    }
    public function getOrderItemWithNullFKmClass()
    {
        return OrderItemWithNullFK::className();
    }

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        $cache = Rock::$app->cache;
        $cache->enabled();
        $cache->flush();
        static::clearRuntime();
    }


    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();
        $cache = Rock::$app->cache;
        $cache->enabled();
        $cache->flush();
        static::clearRuntime();
    }

    protected function setUp()
    {
        parent::setUp();
        ActiveRecord::$db = $this->getConnection();
        Trace::removeAll();
    }

}