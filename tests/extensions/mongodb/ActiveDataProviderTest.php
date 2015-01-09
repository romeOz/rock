<?php

namespace rockunit\extensions\mongodb;

use rock\db\ActiveDataProvider;
use rockunit\extensions\mongodb\models\ActiveRecord;
use rockunit\extensions\mongodb\models\Customer;
use rock\mongodb\Query;


/**
 * @group mongodb
 */
class ActiveDataProviderTest extends MongoDbTestCase
{
    protected function setUp()
    {
        parent::setUp();
        ActiveRecord::$connection = $this->getConnection();
        $this->setUpTestRows();
    }

    protected function tearDown()
    {
        $this->dropCollection(Customer::collectionName());
        parent::tearDown();
    }

    /**
     * Sets up test rows.
     */
    protected function setUpTestRows()
    {
        $collection = $this->getConnection()->getCollection('customer');
        $rows = [];
        for ($i = 1; $i <= 10; $i++) {
            $rows[] = [
                'name' => 'name' . $i,
                'email' => 'email' . $i,
                'address' => 'address' . $i,
                'status' => $i,
            ];
        }
        $collection->batchInsert($rows);
    }

    // Tests :

    public function testQuery()
    {
        $query = new Query;
        $query->from('customer');

        $provider = new ActiveDataProvider([
            'query' => $query,
            'connection' => $this->getConnection(),
        ]);
        $models = $provider->get();
        $this->assertEquals(10, count($models));

        $provider = new ActiveDataProvider([
            'query' => $query,
            'connection' => $this->getConnection(),
            'pagination' => [
                'limit' => 5,
            ]
        ]);
        $models = $provider->get();
        $this->assertEquals(5, count($models));
    }

    public function testActiveQuery()
    {
        $provider = new ActiveDataProvider([
            'query' => Customer::find()->orderBy('id ASC'),
        ]);
        $models = $provider->get();
        $this->assertEquals(10, count($models));
        $this->assertTrue($models[0] instanceof Customer);
        $keys = $provider->getKeys();
        $this->assertTrue($keys[0] instanceof \MongoId);

        $provider = new ActiveDataProvider([
            'query' => Customer::find(),
            'pagination' => [
                'limit' => 5,
            ]
        ]);
        $models = $provider->get();
        $this->assertEquals(5, count($models));
    }
}
