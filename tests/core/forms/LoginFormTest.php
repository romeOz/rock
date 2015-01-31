<?php

namespace rockunit\core\forms;

use rock\csrf\CSRF;
use rock\di\Container;
use rock\Rock;
use rockunit\common\CommonTestTrait;
use rockunit\core\db\DatabaseTestCase;
use rockunit\core\db\models\ActiveRecord;
use rockunit\core\forms\models\LoginFormMock;

/**
 * @group forms
 * @group db
 */
class LoginFormTest extends DatabaseTestCase
{
    use CommonTestTrait;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        Rock::$app->language = 'en';
    }

    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();
        Rock::$app->language = 'ru';
    }

    public function setUp()
    {
        parent::setUp();
        ActiveRecord::$connection = $this->getConnection();
        static::sessionUp();
        Rock::$app->template->removeAllPlaceholders();
        Rock::$app->template->removeAllPlaceholders(true);
    }

    public function tearDown()
    {
        parent::tearDown();
        static::sessionDown();
        static::$post = $_POST;
    }

    /**
     * @dataProvider providerFail
     */
    public function testFail(array $post, array $errors)
    {
        /** @var CSRF $csrf */
        $csrf = Container::load(CSRF::className());
        $model = new LoginFormMock();
        $post[$csrf->csrfParam] = call_user_func($post[$csrf->csrfParam]);
        $_POST = [$model->formName() => $post];
        $model->load($_POST);
        $this->assertFalse($model->validate());
        $this->assertFalse($model->isLogged);
        $this->assertEquals($errors, $model->getErrors());
    }

    public function providerFail()
    {
        return [
            [
                [
                    'email' => ' FOOgmail.ru    ',
                    'password' => 'abc',
                    Rock::$app->csrf->csrfParam => function () {
                        return Rock::$app->csrf->get();
                    },
                ],
                [
                    'email' =>
                        [
                            'e-mail must be valid',
                        ],
                    'password' =>
                        [
                            'password must have a length between 6 and 20',
                        ],
                ]
            ],
            [
                [
                    'email' => 'linda@gmail.com',
                    'password' => '123456f',
                    Rock::$app->csrf->csrfParam => function () {
                        return '';
                    },
                ],
                [
                    'e_login' =>
                        [
                            'CSRF-token must not be empty',
                        ],
                ]
            ],
            [
                [
                    'email' => 'linda@gmail.com',
                    'password' => '123456f',
                    Rock::$app->csrf->csrfParam => function () {
                        return Rock::$app->csrf->get(/*(new LoginForm())->formName()*/);
                    },
                ],
                [
                    'e_login' =>
                        [
                            'Password or email is invalid.',
                        ],
                ]
            ],
            [
                [
                    'email' => 'jane@hotmail.com',
                    'password' => '123456',
                    Rock::$app->csrf->csrfParam => function () {
                        return Rock::$app->csrf->get(/*(new LoginForm())->formName()*/);
                    },
                ],
                [
                    'e_login' =>
                        [
                            'Account is not activated',
                        ],
                ]
            ],
        ];
    }

    public function testSuccess()
    {
        /** @var CSRF $csrf */
        $csrf = Container::load(CSRF::className());
        $post = [
            'email' => 'Linda@gmail.com',
            'password' => '123456',
        ];
        $model = new LoginFormMock();
        $post[$csrf->csrfParam] = $csrf->get();
        $_POST = [$model->formName() => $post];
        $model->load($_POST);
        $this->assertTrue($model->validate());
        $this->assertTrue($model->isLogged);
        $this->assertEquals(
            Rock::$app->user->getAll(['id', 'username']),
            $model->getUsers()->toArray(['id', 'username']));
    }
}
 