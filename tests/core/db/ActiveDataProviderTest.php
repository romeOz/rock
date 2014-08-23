<?php

namespace rockunit\core\db;

use rock\db\ActiveDataProvider;
use rock\db\Query;
use rockunit\core\db\models\ActiveRecord;
use rockunit\core\db\models\Customer;

/**
 * @group db
 * @group mysql
 */
class ActiveDataProviderTest extends DatabaseTestCase
{
    protected function setUp()
    {
        parent::setUp();
        ActiveRecord::$db = $this->getConnection(false);
    }

    public function testActiveQuery()
    {
        // as Array
        $provider = new ActiveDataProvider(
            [
                'query' => Customer::find()->asArray(),
                'only' => ['id', 'name'],
                'pagination' => ['limit' => 2, 'sort' => SORT_DESC]
            ]
        );

        $this->assertSame(count($provider->get()), 2);
        $this->assertNotEmpty($provider->getPagination());
        $this->assertSame($provider->getTotalCount(), 3);
        // to Array
        $result = $provider->toArray()[0];
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertSame(count($result), 2);

        // as ActiveRecord
        $provider = new ActiveDataProvider(
            [
                'query' => Customer::find()->with('profile'),
                'only' => ['id', 'name'],
                'exclude' => ['id'],
                'expand' => ['profile'],
                'pagination' => ['limit' => 2, 'sort' => SORT_DESC]
            ]
        );
        $this->assertSame(count($provider->get()), 2);
        $this->assertNotEmpty($provider->getPagination());
        $this->assertSame($provider->getTotalCount(), 3);

        // to Array
        $result = $provider->toArray()[0];
        $this->assertArrayNotHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('profile', $result);
        $this->assertNotEmpty($result['profile']);
        $this->assertSame(count($result), 2);

        // one + ActiveRecord
        $provider = new ActiveDataProvider(
            [
                'query' => Customer::find()->with('profile')->one(),
                'only' => ['id', 'name'],
                'exclude' => ['id'],
                'expand' => ['profile'],
            ]
        );
        $result = $provider->toArray();
        $this->assertArrayNotHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('profile', $result);
        $this->assertNotEmpty($result['profile']);
        $this->assertSame(count($result), 2);
    }


    public function testQuery()
    {
        $provider = new ActiveDataProvider(
            [
                'query' => (new Query())->setConnection($this->getConnection(false))->from('customer'),
                'only' => ['id', 'name'],
                'pagination' => ['limit' => 2, 'sort' => SORT_DESC]
            ]
        );

        // get
        $this->assertSame(count($provider->get()), 2);
        $this->assertNotEmpty($provider->getPagination());
        $this->assertSame($provider->getTotalCount(), 3);

        // to Array
        $result = $provider->toArray()[0];
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertSame(count($result), 2);
    }

    public function testArray()
    {
        $provider = new ActiveDataProvider(
            [
                'query' => (new Query())->from('customer')->all($this->getConnection(false)),
                'only' => ['id', 'name'],
                'pagination' => ['limit' => 2, 'sort' => SORT_DESC]
            ]
        );

        // get
        $this->assertSame(count($provider->get()), 2);
        $this->assertNotEmpty($provider->getPagination());
        $this->assertSame($provider->getTotalCount(), 3);

        // to Array
        $result = $provider->toArray()[0];
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertSame(count($result), 2);

        $provider = new ActiveDataProvider(
            [
                'query' => (new Query())->from('customer')->one($this->getConnection(false)),
                'only' => ['id', 'name'],
            ]
        );

        $result = $provider->toArray();
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertSame(count($result), 2);
    }
}