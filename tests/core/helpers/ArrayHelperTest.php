<?php

namespace rockunit\core\helpers;

use rock\components\Model;
use rock\helpers\ArrayHelper;
use rock\helpers\Json;
use rock\helpers\Serialize;
use rockunit\core\db\DatabaseTestCase;
use rockunit\core\db\models\ActiveRecord;
use rockunit\core\db\models\Item;

/**
 * @group base
 * @group helpers
 */
class ArrayHelperTest extends DatabaseTestCase
{
    /**
     * @group php
     * @dataProvider providerToArray
     */
    public function testToArray($expected, $actual, $only = [], $exclude = [])
    {
        $this->assertSame(ArrayHelper::toArray($expected, $only, $exclude, true), $actual);
    }

    public function providerToArray()
    {
        return [
            [
                new Post_1(),
                ['name' => 'Tom', 'email' => 'tom@site.com']
            ],
            [
                [
                    new Post_1(),
                    new Post_2(),
                ],
                [
                    ['name' => 'Tom', 'email' => 'tom@site.com'],
                    ['name' => 'Jane', 'email' => 'jane@site.com'],
                ]
            ],
        ];
    }

    /**
     * @group db
     * @dataProvider providerToArrayWithDb
     */
    public function testToArrayWithDb($expected, $actual, $only = [], $exclude = [])
    {
        if (!class_exists('\rock\db\Connection')) {
            $this->markTestSkipped('Rock db not installed.');
        }
        ActiveRecord::$connection = $this->getConnection(false);
        $expected = call_user_func($expected);
        $this->assertSame(ArrayHelper::toArray($expected, $only, $exclude, true), $actual);
    }
    public function providerToArrayWithDb()
    {
        return [
                        [
                            function(){
                                return Item::find()->one();
                            },
                            [
                                'id' => 1,
                                'name' => 'Monkey Island',
                                'category_id' => 1
                            ]
                        ],
                        [
                            function(){
                                return Item::find()->one();
                            },
                            ['name' => 'Monkey Island'],
                            ['name']
                        ],
                        [
                            function(){
                                return Item::find()->one();
                            },
                            ['id' => 1],
                            [],
                            ['name', 'category_id']
                        ],
                        [
                            function(){
                                return Item::find()->limit(2)->all();
                            },
                            [
                                ['id' => 1, 'name' => 'Monkey Island', 'category_id' => 1],
                                ['id' => 2, 'name' => 'Full Throttle', 'category_id' => 1]
                            ]
                        ],
                        [
                            function(){
                                return Item::find()->limit(2)->all();
                            },
                            [
                                ['name' => 'Monkey Island'],
                                ['name' => 'Full Throttle']
                            ],
                            ['name']
                        ],
                        [
                            function(){
                                return Item::find()->limit(2)->all();
                            },
                            [
                                ['id' => 1],
                                ['id' => 2]
                            ],
                            [],
                            ['name', 'category_id']
                        ],
        ];
    }
}


class Post_1 extends Model
{
    public $name = 'Tom';
    public $email = 'tom@site.com';
}

class Post_2 extends Model
{
    public $name = 'Jane';
    public $email = 'jane@site.com';
}