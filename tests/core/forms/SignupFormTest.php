<?php

namespace rockunit\core\forms;

use rock\i18n\i18n;
use rock\Rock;
use rock\validation\Validation;
use rockunit\core\db\DatabaseTestCase;
use rockunit\core\db\models\ActiveRecord;
use rockunit\core\db\models\BaseUsers;
use rockunit\core\forms\models\SignupForm;

/**
 * @group forms
 * @group db
 */
class SignupFormTest extends DatabaseTestCase
{
    public static $session = [];
    public static $post = [];
    public static $cookie = [];

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        Validation::setLocale(i18n::EN);
        Rock::$app->language = i18n::EN;
    }

    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();
        Validation::setLocale(i18n::RU);
        Rock::$app->language = i18n::RU;
    }

    public function setUp()
    {
        parent::setUp();
        ActiveRecord::$db = $this->getConnection();
        $_SESSION = static::$session;
        $_COOKIE = static::$cookie;
        $_POST = static::$post;
        Rock::$app->session->removeAll();
        Rock::$app->cookie->removeAll();
        Rock::$app->template->removeAllPlaceholders();
        Rock::$app->template->removeAllPlaceholders(true);
    }

    public function tearDown()
    {
        parent::tearDown();
        static::$session = $_SESSION;
        static::$cookie = $_COOKIE;
        static::$post = $_POST;
    }

    /**
     * @dataProvider providerFail
     */
    public function testFail(array $post, array $placeholders)
    {
        $signupForm = new SignupForm();
        $post[Rock::$app->token->csrfPrefix] = call_user_func($post[Rock::$app->token->csrfPrefix]);
        $_POST = [$signupForm->formName() => $post];
        $signupForm->load($_POST);
        $signupForm->validate();
        $this->assertFalse($signupForm->isSignup);
        $this->assertEquals(Rock::$app->template->getAllPlaceholders(false, true), $placeholders);
    }

    public function providerFail()
    {
        return [
            [
                [
                    'email' => ' FOOgmail.ru    ',
                    'username' => '',
                    'password' => 'abc',
                    Rock::$app->token->csrfPrefix => function(){ return Rock::$app->token->create((new SignupForm())->formName());},
                    'password_confirm' => 'abc',
                    'captcha' => '12345'
                ],
                [
                    'e_email' => '"foogmail.ru" must be valid email',
                    'e_username' => 'the value must not be empty',
                    'e_password' => "password must have a length between 6 and 20",
                    'e_captcha' => 'captcha must be valid'
                ]
            ],
            [
                [
                    'email' => '',
                    'username' => 'foo',
                    'password' => '123456',
                    'password_confirm' => '123456',
                    Rock::$app->token->csrfPrefix => function(){ return Rock::$app->token->create((new SignupForm())->formName());},
                    'captcha' => ''
                ],
                [
                    'e_email' => 'the value must not be empty',
                    'e_captcha' => 'the value must not be empty'
                ]
            ],
            [
                [
                    'email' => 'foo@gmail',
                    'username' => 'foo',
                    'password' => 'abc',
                    'password_confirm' => 'abcde',
                    Rock::$app->token->csrfPrefix => function(){ return Rock::$app->token->create((new SignupForm())->formName());},
                ],
                [
                    'e_email' => '"foo@gmail" must be valid email',
                    'e_password' => "password must have a length between 6 and 20",
                    'e_password_confirm' => 'values must be equals',
                    'e_captcha' => 'the value must not be empty'
                ]
            ],
            [
                [
                    'email' => 'foogmail.ru',
                    'username' => '',
                    'password' => 'abc',
                    Rock::$app->token->csrfPrefix => function () {
                            return '';
                        },
                    'password_confirm' => 'abc',
                    'captcha' => '12345'
                ],
                [
                    'e_signup' => 'the value must not be empty',
                ]
            ],
        ];
    }

    public function testExistsUserByUsernameFail()
    {
        $post = [
            'email' => 'foo@gmail.ru',
            'username' => 'Jane',
            'password' => '123456',
            'password_confirm' => '123456',
            Rock::$app->token->csrfPrefix => function(){ return Rock::$app->token->create((new SignupForm())->formName());},
            'captcha' => '12345'
        ];
        Rock::$app->session->setFlash('captcha', '12345');
        $signupForm = (new SignupForm());
        $post[Rock::$app->token->csrfPrefix] = call_user_func($post[Rock::$app->token->csrfPrefix]);
        $_POST = [$signupForm->formName() => $post];
        $signupForm->load($_POST);
        $signupForm->validate();
        $this->assertFalse($signupForm->isSignup);
        $this->assertEquals(
            Rock::$app->template->getAllPlaceholders(false, true),
            [
                'e_signup' =>
                    "User with this name/e-mail already exists."
            ]
        );
    }

    public function testExistsUserByEmailFail()
    {
        $post = [
            'email' => 'jane@hotmail.com',
            'username' => 'Chuck',
            'password' => '123456',
            'password_confirm' => '123456',
            Rock::$app->token->csrfPrefix => function(){ return Rock::$app->token->create((new SignupForm())->formName());},
            'captcha' => '12345'
        ];
        Rock::$app->session->setFlash('captcha', '12345');
        $post[Rock::$app->token->csrfPrefix] = call_user_func($post[Rock::$app->token->csrfPrefix]);
        $signupForm = (new SignupForm());
        $_POST = [$signupForm->formName() => $post];
        $signupForm->load($_POST);
        $signupForm->validate();
        $this->assertFalse($signupForm->isSignup);
        $this->assertEquals(
            Rock::$app->template->getAllPlaceholders(false, true),
            [
                'e_signup' =>
                    "User with this name/e-mail already exists."
            ]
        );
    }


    public function testCaptchaFail()
    {
        $post = [
            'email' => 'foo@gmail.ru',
            'username' => 'Jane',
            'password' => '123456',
            'password_confirm' => '123456',
            Rock::$app->token->csrfPrefix => function(){ return Rock::$app->token->create((new SignupForm())->formName());},
            'captcha' => '1234'
        ];
        Rock::$app->session->setFlash('captcha', '12345');
        $post[Rock::$app->token->csrfPrefix] = call_user_func($post[Rock::$app->token->csrfPrefix]);
        $signupForm = (new SignupForm());
        $_POST = [$signupForm->formName() => $post];
        $signupForm->load($_POST);
        $signupForm->validate();
        $this->assertFalse($signupForm->isSignup);
        $this->assertEquals(
            Rock::$app->template->getAllPlaceholders(false, true),
            [
                'e_captcha' =>
                    "captcha must be valid"
            ]
        );
    }


    public function testSuccess()
    {
        $post = [
            'email' => 'chuck@gmail.ru',
            'username' => 'Chuck',
            'password' => '123456',
            'password_confirm' => '123456',
            Rock::$app->token->csrfPrefix => function(){ return Rock::$app->token->create((new SignupForm())->formName());},
            'captcha' => '12345'
        ];
        Rock::$app->session->setFlash('captcha', '12345');
        $post[Rock::$app->token->csrfPrefix] = call_user_func($post[Rock::$app->token->csrfPrefix]);
        $signupForm = (new SignupForm());
        $_POST = [$signupForm->formName() => $post];
        $signupForm->load($_POST);
        $signupForm->validate();
        $this->assertTrue($signupForm->isSignup);
        $this->assertTrue(BaseUsers::find()->byUsername('Chuck')->exists());
        $this->assertTrue((bool)BaseUsers::deleteByUsername('Chuck'));
    }
}
 