<?php

namespace rockunit\core\forms;


use rock\i18n\i18n;
use rock\Rock;
use rock\validation\Validation;
use rockunit\common\CommonTrait;
use rockunit\core\db\DatabaseTestCase;
use rockunit\core\db\models\ActiveRecord;
use rockunit\core\forms\models\LoginForm;

/**
 * @group forms
 * @group db
 */
class LoginFormTest extends DatabaseTestCase
{

    use CommonTrait;

    public static $post = [];

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        Validation::setLocale(i18n::EN);
        Rock::$app->language = i18n::EN;
    }


    public function setUp()
    {
        parent::setUp();
        ActiveRecord::$db = $this->getConnection();
        $_POST = static::$post;
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

    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();
        Validation::setLocale(i18n::RU);
        Rock::$app->language = i18n::RU;
    }

    /**
     * @dataProvider providerFail
     */
    public function testFail(array $post, array $placeholders)
    {
        $loginForm = new LoginForm();
        $post[Rock::$app->token->csrfParam] = call_user_func($post[Rock::$app->token->csrfParam]);
        $_POST = [$loginForm->formName() => $post];
        $loginForm->load($_POST);
        $loginForm->validate();
        $this->assertFalse($loginForm->isLogin);
        $this->assertEquals(Rock::$app->template->getAllPlaceholders(false, true), $placeholders);
    }

    public function providerFail()
    {
        return [
            [
                [
                    'email' => ' FOOgmail.ru    ',
                    'password' => 'abc',
                    Rock::$app->token->csrfParam => function(){ return Rock::$app->token->create((new LoginForm())->formName());},
                ],
                [
                    'e_email' => '"foogmail.ru" must be valid email',
                    'e_password' => "password must have a length between 6 and 20",
                ]
            ],
            [
                [
                    'email' => 'linda@gmail.com',
                    'password' => '123456f',
                    Rock::$app->token->csrfParam => function(){return '';},
                ],
                [
                    'e_login' => 'the value must not be empty',
                ]
            ],
            [
                [
                    'email' => 'linda@gmail.com',
                    'password' => '123456f',
                    Rock::$app->token->csrfParam => function(){ return Rock::$app->token->create((new LoginForm())->formName());},
                ],
                [
                    'e_login' => 'Password or email is invalid.',
                ]
            ],
            [
                [
                    'email' => 'jane@hotmail.com',
                    'password' => '123456',
                    Rock::$app->token->csrfParam => function(){ return Rock::$app->token->create((new LoginForm())->formName());},
                ],
                [
                    'e_login' => 'Account is not activated',
                ]
            ],
        ];
    }

    public function testSuccess()
    {
        $token = Rock::$app->token;
        $post = [
            'email' => 'Linda@gmail.com',
            'password' => '123456',
        ];

        $loginForm = new LoginForm();
        $post[$token->csrfParam] = $token->create($loginForm->formName());
        $_POST = [$loginForm->formName() => $post];
        $loginForm->load($_POST);
        $loginForm->validate();
        $this->assertTrue($loginForm->isLogin);
        $this->assertEquals(Rock::$app->user->getAll(['id', 'username']), $loginForm->getUsers()->toArray(['id', 'username']));
    }
}
 