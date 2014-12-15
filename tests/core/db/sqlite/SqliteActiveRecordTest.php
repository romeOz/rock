<?php
namespace rockunit\core\db\sqlite;

use rock\db\ActiveQuery;
use rockunit\core\db\ActiveRecordTest;
use rockunit\core\db\models\Category;
use rockunit\core\db\models\Order;

/**
 * @group db
 * @group sqlite
 */
class SqliteActiveRecordTest extends ActiveRecordTest
{
    protected $driverName = 'sqlite';

    public function testIssues()
    {
        // https://github.com/yiisoft/yii2/issues/4938
        $category = Category::findOne(2);
        $this->assertTrue($category instanceof Category);
        $this->assertEquals(3, $category->getItems()->count());
        $this->assertEquals(1, $category->getLimitedItems()->count());
        //$this->assertEquals(1, $category->getLimitedItems()->distinct(true)->count());

        // https://github.com/yiisoft/yii2/issues/3197
        $orders = Order::find()->with('orderItems')->orderBy('id')->all();
        $this->assertEquals(3, count($orders));
        $this->assertEquals(2, count($orders[0]->orderItems));
        $this->assertEquals(3, count($orders[1]->orderItems));
        $this->assertEquals(1, count($orders[2]->orderItems));
        $orders = Order::find()->with(['orderItems' => function (ActiveQuery $q) { $q->indexBy('item_id'); }])->orderBy('id')->all();
        $this->assertEquals(3, count($orders));
        $this->assertEquals(2, count($orders[0]->orderItems));
        $this->assertEquals(3, count($orders[1]->orderItems));
        $this->assertEquals(1, count($orders[2]->orderItems));
    }
}
