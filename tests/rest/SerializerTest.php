<?php

namespace rockunit\rest;


use rock\components\Model;
use rock\db\common\ActiveDataProvider;
use rock\response\Response;
use rock\rest\Serializer;
use rockunit\core\db\DatabaseTestCase;
use rockunit\core\db\models\ActiveRecord;
use rockunit\core\db\models\Users;

class SerializerTest extends DatabaseTestCase
{
    protected function setUp()
    {
        parent::setUp();
        ActiveRecord::$connection = $this->getConnection(false);
    }

    public function testAsActiveDataProvider()
    {
        $response = new Response();
        // as Array
        $provider = new ActiveDataProvider(
            [
                'query' => Users::find()->select(['id', 'username'])->asArray(),
                'pagination' => ['limit' => 2, 'sort' => SORT_DESC]
            ]
        );

        $expected = [
            'data' =>
                [
                    0 =>
                        [
                            'id' => 1,
                            'username' => 'Tom',
                        ],
                    1 =>
                        [
                            'id' => 2,
                            'username' => 'Jane',
                        ],
                ],
            '_links' =>
                [
                    'self' =>
                        [
                            'href' => 'http://site.com/',
                        ],
                    'first' =>
                        [
                            'href' => 'http://site.com/',
                        ],
                    'prev' =>
                        [
                            'href' => 'http://site.com/',
                        ],
                    'next' =>
                        [
                            'href' => 'http://site.com/?page=1',
                        ],
                    'last' =>
                        [
                            'href' => 'http://site.com/?page=1',
                        ],
                ],
            '_meta' =>
                [
                    'totalCount' => 3,
                    'pageCount' => 2,
                    'currentPage' => 0,
                    'perPage' => 2,
                ],
        ];
        $serialize =  new Serializer(['collectionEnvelope' => 'data', 'response' => $response]);
        $this->assertEquals($expected, $serialize->serialize($provider));
        $this->assertNotEmpty($response->getHeaders()->get(strtolower($serialize->totalCountHeader)));
        $this->assertNotEmpty($response->getHeaders()->get('link'));
    }

    public function testAsModel()
    {
        $response = new Response();
        $serialize =  new Serializer(['collectionEnvelope' => 'data', 'response' => $response]);
        $_GET[$serialize->fieldsParam] = 'username,email';
        $model = Users::find()->select(['id', 'username', 'email'])->one();

        $expected = [
            'username' => 'Tom',
            'email' => 'tom@gmail.com',
            '_links' =>
                [
                    'self' =>
                        [
                            'href' => 'http://site.com/users/Tom',
                        ],
                ],
        ];
        $this->assertEquals($expected, $serialize->serialize($model));
    }

    public function testAsModelForm()
    {
        $model = new TestModel();
        $post = [
            'username' => 'Tom',
            'email' => 'foo@email'
        ];
        $model->attributes = $post;
        $this->assertFalse($model->validate());
        $this->assertNotEmpty($model->getErrors());
        $response = new Response();
        $serialize =  new Serializer(['collectionEnvelope' => 'data', 'response' => $response]);
        $expected = [
            'email'=>'email must be valid'
        ];

        $this->assertEquals($expected, $serialize->serialize($model));

        // all errors

        $serialize =  new Serializer(['collectionEnvelope' => 'data', 'response' => $response, 'firstErrors' => false]);
        $expected = [
            'email'=> ['email must be valid']
        ];

        $this->assertEquals($expected, $serialize->serialize($model));
    }
}

class TestModel extends Model
{
    /** @var  string */
    public $email;
    /** @var  string */
    public $username;


    public function rules()
    {
        return [
            [
                ['email', 'username'], 'trim'
            ],
            [
                ['email', 'username'], 'required',
            ],
            [
                'email', 'length' => [4, 80, true], 'email'
            ],
            [
                'email', '!lowercase'
            ],
            [
                ['email', 'username'], 'removeTags'
            ],
        ];
    }

    public function safeAttributes()
    {
        return ['email', 'username'];
    }
}
