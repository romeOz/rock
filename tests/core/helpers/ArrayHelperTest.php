<?php

namespace rockunit\core\helpers;

use rock\base\Model;
use rock\helpers\ArrayHelper;
use rock\helpers\Json;
use rock\helpers\Serialize;
use rockunit\core\db\DatabaseTestCase;
use rockunit\core\db\models\ActiveRecord;
use rockunit\core\db\models\Item;

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

/**
 * @group base
 * @group helpers
 */
class ArrayHelperTest extends DatabaseTestCase
{
    /**
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
                [
                    'name' => 'Tom',
                    'email' => 'test@site.com',
                ],
                [
                    'name' => 'Tom',
                    'email' => 'test@site.com',
                ],
            ],
            [
                [
                    'names' => Json::encode(['Tom', 'Jane']),
                    'email' => 'test@site.com',
                ],
                [
                    'names' => ['Tom', 'Jane'],
                    'email' => 'test@site.com',
                ],
            ],
            [
                [
                    'orders' => ['names' => Json::encode(['Tom', 'Jane'])],
                    'email' => 'test@site.com',
                ],
                [
                    'orders' => ['names' => ['Tom', 'Jane']],
                    'email' => 'test@site.com',
                ],
            ],
            [
                (object)[
                    'name' => 'Tom',
                    'email' => 'test@site.com',
                ],
                [
                    'name' => 'Tom',
                    'email' => 'test@site.com',
                ],
            ],
            [
                (object)[
                    'names' => Json::encode(['Tom', 'Jane']),
                    'email' => 'test@site.com',
                ],
                [
                    'names' => ['Tom', 'Jane'],
                    'email' => 'test@site.com',
                ],
            ],
            [
                (object)[
                    'name' => 'Tom',
                    'emails' => ['test@site.com', 'tom@site.com'],
                ],
                [
                    'name' => 'Tom',
                    'emails' => ['test@site.com', 'tom@site.com'],
                ],
            ],
            [
                (object)[
                    'name' => 'Tom',
                    'orders' => [
                        'order_1' => ['name' => 'name_1'],
                        'order_2' => ['name' => 'name_2']
                    ],
                ],
                [
                    'name' => 'Tom',
                    'orders' => [
                        'order_1' => ['name' => 'name_1'],
                        'order_2' => ['name' => 'name_2']
                    ],
                ],
            ],

            [
                Serialize::serialize([
                    'name' => 'Tom',
                    'orders' => [
                        'order_1' => ['name' => 'name_1'],
                        'order_2' => ['name' => 'name_2']
                    ],
                ], Serialize::SERIALIZE_JSON),
                [
                    'name' => 'Tom',
                    'orders' => [
                        'order_1' => ['name' => 'name_1'],
                        'order_2' => ['name' => 'name_2']
                    ],
                ],
            ],
            [
                Serialize::serialize([
                                 'name' => 'Tom',
                                 'orders' => [
                                     'order_1' => ['name' => 'name_1'],
                                     'order_2' => ['name' => 'name_2']
                                 ],
                             ]),
                [
                    'name' => 'Tom',
                    'orders' => [
                        'order_1' => ['name' => 'name_1'],
                        'order_2' => ['name' => 'name_2']
                    ],
                ],
            ],

            [
                Json::decode(Json::encode([
                                         'name' => 'Tom',
                                         'orders' => [
                                             'order_1' => ['name' => 'name_1'],
                                             'order_2' => ['name' => 'name_2']
                                         ],
                                     ]), false),
                [
                    'name' => 'Tom',
                    'orders' => [
                        'order_1' => ['name' => 'name_1'],
                        'order_2' => ['name' => 'name_2']
                    ],
                ],
            ],
            [
                (object)[
                    'name' => 'Tom',
                    'orders' => (object)[
                            'order_1' => ['name' => 'name_1'],
                            'order_2' => ['name' => 'name_2']
                        ],
                ],
                [
                    'name' => 'Tom',
                    'orders' => [
                        'order_1' => ['name' => 'name_1'],
                        'order_2' => ['name' => 'name_2']
                    ],
                ],
            ],
            [
                (object)[
                    'name' => 'Tom',
                    'orders' => (object)[
                            'order_1' => Json::encode(['name' => 'name_1']),
                            'order_2' => Json::encode(['name' => 'name_2'])
                        ],
                ],
                [
                    'name' => 'Tom',
                    'orders' => [
                        'order_1' => ['name' => 'name_1'],
                        'order_2' => ['name' => 'name_2']
                    ],
                ],
            ],
            [
                [
                    'name' => 'Tom',
                    'email' => (object)'test@site.com',
                ],
                [
                    'name' => 'Tom',
                    'email' => 'test@site.com',
                ],
            ],
            [
                [
                    'name' => (object)'Tom',
                    'email' => (object)'test@site.com',
                ],
                [
                    'name' => 'Tom',
                    'email' => 'test@site.com',
                ],
            ],
            [
                [
                    'names' => ['Tom', 'Jane'],
                    'emails' => ['test@site.com', 'jane@site.com'],
                ],
                [
                    'names' => ['Tom', 'Jane'],
                    'emails' => ['test@site.com', 'jane@site.com'],
                ],
            ],
            [
                [
                    ['id' => 1, 'title' => 'title1'],
                    ['id' => 2, 'title' => 'title2'],
                ],
                [
                    ['id' => 1, 'title' => 'title1'],
                    ['id' => 2, 'title' => 'title2'],
                ],
            ],
            [
                [
                    (object)['id' => 1, 'title' => 'title1'],
                    (object)['id' => 2, 'title' => 'title2'],
                ],
                [
                    ['id' => 1, 'title' => 'title1'],
                    ['id' => 2, 'title' => 'title2'],
                ],
            ],
            [
                [
                    (object)['id', 'title', 'title1'],
                    (object)['id', 'title', 'title2'],
                ],
                [
                    [],
                    [],
                ],
            ],
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
        ActiveRecord::$db = $this->getConnection(false);
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

    public function testToSingle()
    {
        $this->assertEquals(
            ArrayHelper::toSingle(
                [
                    'aa' => 'text',
                    'bb' => ['aa' => 'text2'],
                    'cc' => [
                        'aa' =>
                            ['gg' => 'text3']
                    ]
                ]
            ),
            ['aa' => 'text', 'bb.aa' => 'text2', 'cc.aa.gg' => 'text3']
        );
    }


    /**
     * @dataProvider providerToMulti
     */
    public function testToMulti($expected, $actual, $recursive = false)
    {
        $this->assertEquals(ArrayHelper::toMulti($expected, '.', $recursive),$actual);
    }

    public function providerToMulti()
    {
        return [
            [
                ['aa' => 'text', 'bb.aa' => 'text2', 'cc.aa.gg' => ['aa.bb' => 'text3']],
                [
                    'aa' => 'text',
                    'bb' => ['aa' => 'text2'],
                    'cc' => [
                        'aa' =>
                            ['gg' => ['aa.bb' => 'text3']]
                    ]
                ],
            ],
            [
                ['aa' => 'text', 'bb.aa' => 'text2', 'cc.aa.gg' => ['aa.bb' => 'text3']],
                [
                    'aa' => 'text',
                    'bb' => ['aa' => 'text2'],
                    'cc' => [
                        'aa' =>
                            ['gg' => ['aa' => ['bb'=>'text3']]]
                    ]
                ],
                true
            ],
            [
                ['aa' => 'text', 'bb.aa' => 'text2', 'cc.aa.gg' => ['aa' => ['aa.bb' => 'text3']]],
                [
                    'aa' => 'text',
                    'bb' => ['aa' => 'text2'],
                    'cc' => [
                        'aa' =>
                            ['gg' => ['aa' => ['aa'=> ['bb'=>'text3']]]]
                    ]
                ],
                true
            ],

            [
                ['aa' => 'text', 'bb.aa' => 'text2', 'cc.aa.gg' => ['aa' => ['aa.bb' => 'text3']], ['dd.bb' => ['aa.cc' => 'text3']]],
                [
                    'aa' => 'text',
                    'bb' => ['aa' => 'text2'],
                    'cc' => [
                        'aa' =>
                            ['gg' => ['aa' => ['aa'=> ['bb'=>'text3']]]]
                    ],
                    ['dd' => ['bb' => ['aa' => ['cc' => 'text3']]]]
                ],
                true
            ],
            [
                ['aa' => 'text', 'bb.aa' => 'text2', 'bb.cc' => ['dd' => ['gg.aa' => 'text3']]],
                [
                    'aa' => 'text',
                    'bb' => ['aa' => 'text2', 'cc' => ['dd' => ['gg'=> ['aa'=> 'text3']]]],

                ],
                true
            ],
        ];
    }


    /**
     * @dataProvider providerRemove
     */
    public function testRemove($expected, $actual, $keys)
    {
        $this->assertEquals(ArrayHelper::removeValue($expected, $keys), $actual);
    }

    public function providerRemove()
    {
        return [
            [
                ['type' => 'A', 'options' => [1, 2]],
                ['options' => [1, 2]],
                'type'
            ],

            [
                ['type' => 'A', 'options' => [1, 2]],
                ['options' => [1, 2]],
                ['type']
            ],
            [
                ['type' => 'A', 'options' => ['name' => 'option_1', 'params' => ['param1', 'param2']]],
                ['type' => 'A', 'options' => ['name' => 'option_1']],
                ['options', 'params']
            ],

            [
                ['type' => 'A', 'options' => ['name' => 'option_1', 'params' => ['param1', 'param2']]],
                ['type' => 'A', 'options' => ['name' => 'option_1', 'params' => ['param1']]],
                ['options', 'params', 1]
            ],
        ];
    }


    /**
     * @dataProvider providerMove
     */
    public function testMove($expected, $actual, $key, $move = ArrayHelper::MOVE_HEAD)
    {
        $this->assertSame(ArrayHelper::moveElement($expected, $key, $move), $actual);
    }

    public function providerMove()
    {
        return [

            [
                ['id' => 1, 'title' => 'text3', 'params' => ['param_1', 'param_2']],
                ['title' => 'text3', 'id' => 1, 'params' => ['param_1', 'param_2']],
                'title'
            ],

            [
                ['id' => 1, 'title' => 'text3', 'params' => ['param_1', 'param_2']],
                ['title' => 'text3', 'params' => ['param_1', 'param_2'], 'id' => 1],
                'id',
                ArrayHelper::MOVE_TAIL
            ],

            [
                ['id' => 1, 'title' => 'text3', 'params' => ['param_1', 'param_2']],
                ['id' => 1, 'title' => 'text3', 'params' => ['param_1', 'param_2']],
                'params',
                ArrayHelper::MOVE_TAIL
            ],
            [
                ['id' => 1, 'title' => 'text3', 'params' => ['param_1', 'param_2']],
                ['id' => 1, 'title' => 'text3', 'params' => ['param_1', 'param_2']],
                'id',
                ArrayHelper::MOVE_HEAD
            ],
        ];
    }


    /**
     * @dataProvider valueProvider
     *
     * @param $key
     * @param $expected
     * @param null $default
     */
    public function testGetValue($key, $expected, $default = null)
    {
        $array = [
            'name' => 'test',
            'date' => '31-12-2113',
            'post' => [
                'id' => 5,
                'author' => [
                    'name' => 'romeo',
                    'profile' => [
                        'title' => '1337',
                    ],
                ],
            ],
            'admin.firstname' => 'Sergey',
            'admin.lastname' => 'Galka',
            'admin' => [
                'lastname' => 'romeo',
            ],
        ];

        $this->assertEquals($expected, ArrayHelper::getValue($array, $key, $default));
    }


    public function valueProvider()
    {
        return [
            ['name', 'test'],
            ['noname', null],
            ['noname', 'test', 'test'],
            ['post.id', 5],
            [['post', 'id'], 5],
            ['post.id', 5, 'test'],
            ['nopost.id', null],
            ['nopost.id', 'test', 'test'],
            ['post.author.name', 'romeo'],
            ['post.author.noname', null],
            ['post.author.noname', 'test', 'test'],
            ['post.author.profile.title', '1337'],
            ['admin.firstname', 'Sergey'],
            ['admin.firstname', 'Sergey', 'test'],
            ['admin.lastname', 'Galka'],
            [
                function ($array, $defaultValue) {
                    return $array['date'] . $defaultValue;
                },
                '31-12-2113test',
                'test'
            ],
            [[], [
                'name' => 'test',
                'date' => '31-12-2113',
                'post' => [
                    'id' => 5,
                    'author' => [
                        'name' => 'romeo',
                        'profile' => [
                            'title' => '1337',
                        ],
                    ],
                ],
                'admin.firstname' => 'Sergey',
                'admin.lastname' => 'Galka',
                'admin' => [
                    'lastname' => 'romeo',
                ],
            ]],
        ];
    }

    public function testGetValueAsObject()
    {
        $object = new \stdClass();
        $subobject = new \stdClass();
        $subobject->bar = 'test';
        $object->foo = $subobject;
        $object->baz = 'text';
        $this->assertSame(ArrayHelper::getValue($object, 'foo.bar'), 'test');
        $this->assertSame(ArrayHelper::getValue($object, ['foo', 'bar']), 'test');
        $this->assertSame(ArrayHelper::getValue($object, 'baz'), 'text');
    }

    public function testGetColumn()
    {
        $array = [
            'a' => ['id' => '123', 'data' => 'abc'],
            'b' => ['id' => '345', 'data' => 'def'],
        ];
        $result = ArrayHelper::getColumn($array, 'id');
        $this->assertEquals(['a' => '123', 'b' => '345'], $result);
        $result = ArrayHelper::getColumn($array, 'id', false);
        $this->assertEquals(['123', '345'], $result);

        $result = ArrayHelper::getColumn($array, function ($element) {
                return $element['data'];
            });
        $this->assertEquals(['a' => 'abc', 'b' => 'def'], $result);
        $result = ArrayHelper::getColumn($array, function ($element) {
                return $element['data'];
            }, false);
        $this->assertEquals(['abc', 'def'], $result);
    }

    public function testIntersectByKeys()
    {
        $this->assertSame(ArrayHelper::intersectByKeys(['foo'=> 'foo', 'bar' => 'bar'], ['bar']), ['bar' => 'bar']);
    }

    public function testDiffByKeys()
    {
        $this->assertSame(ArrayHelper::diffByKeys(['foo'=> 'foo', 'bar' => 'bar'], ['bar']), ['foo' => 'foo']);
    }

    public function testMap()
    {
        $callback = function() {
            return 'test';
        };
        $this->assertSame(ArrayHelper::map(['foo' => 'foo', 'bar' => 'bar'], $callback, false, 1), ['foo' => 'test', 'bar' => 'bar']);

        // recursive
        $this->assertSame(ArrayHelper::map(['foo' => 'foo', 'bar' => ['baz' => 'baz']], $callback, true), ['foo' => 'test', 'bar' => ['baz' => 'test']]);
    }
}
 