<?php

namespace rockunit\core\forms;


use rock\i18n\i18n;
use rock\Rock;
use rock\validation\Validation;
use rockunit\core\db\DatabaseTestCase;
use rockunit\core\db\models\ActiveRecord;
use rockunit\core\db\models\BaseUsers;
use rockunit\core\forms\models\RecoveryForm;
use rockunit\core\forms\models\SignupForm;
use rockunit\mocks\UserMock;

/**
 * @group forms
 * @group db
 */
class RecoveryFormTest extends DatabaseTestCase
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
        $post[Rock::$app->token->csrfParam] = call_user_func($post[Rock::$app->token->csrfParam]);
        $recoveryForm = (new RecoveryForm());
        $_POST = [$recoveryForm->formName() => $post];
        $recoveryForm->load($_POST);
        $recoveryForm->validate();
        $this->assertFalse($recoveryForm->isRecovery);
        $this->assertEquals(Rock::$app->template->getAllPlaceholders(false, true), $placeholders);
    }

    public function providerFail()
    {
        return [
            [
                [
                    'email' => '        fooGMAIL.ru  ',
                    Rock::$app->token->csrfParam => function(){ return Rock::$app->token->create((new RecoveryForm())->formName());},
                    'captcha' => '12345'
                ],
                [
                    'e_email' => '"foogmail.ru" must be valid email',
                    'e_captcha' => 'captcha must be valid'
                ]
            ],
            [
                [
                    'email' => '',
                    Rock::$app->token->csrfParam => function(){ return Rock::$app->token->create((new RecoveryForm())->formName());},
                    'captcha' => ''
                ],
                [
                    'e_email' => 'the value must not be empty',
                    'e_captcha' => 'the value must not be empty'
                ]
            ],
            [
                [
                    'email' => 'foogmail.ru',
                    Rock::$app->token->csrfParam => function () {
                            return '';
                        },
                    'captcha' => '12345'
                ],
                [
                    'e_recovery' => 'the value must not be empty',
                ]
            ],
        ];
    }

    public function testExistsUserByEmailFail()
    {
        $post = [
            'email' => 'chuck@hotmail.com',
            'username' => 'Chuck',
            'password' => '123456',
            'password_confirm' => '123456',
            Rock::$app->token->csrfParam => function(){ return Rock::$app->token->create((new RecoveryForm())->formName());},
            'captcha' => '12345'
        ];
        Rock::$app->session->setFlash('captcha', '12345');
        $post[Rock::$app->token->csrfParam] = call_user_func($post[Rock::$app->token->csrfParam]);
        $recoveryForm = (new RecoveryForm());
        $_POST = [$recoveryForm->formName() => $post];
        $recoveryForm->load($_POST);
        $recoveryForm->validate();
        $this->assertFalse($recoveryForm->isRecovery);
        $this->assertEquals(
            Rock::$app->template->getAllPlaceholders(false, true),
            [
                'e_recovery' =>
                    "Email is invalid."
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
            Rock::$app->token->csrfParam => function(){ return Rock::$app->token->create((new RecoveryForm())->formName());},
            'captcha' => '1234'
        ];
        Rock::$app->session->setFlash('captcha', '12345');
        $post[Rock::$app->token->csrfParam] = call_user_func($post[Rock::$app->token->csrfParam]);
        $recoveryForm = (new RecoveryForm());
        $_POST = [$recoveryForm->formName() => $post];
        $recoveryForm->load($_POST);
        $recoveryForm->validate();
        $this->assertFalse($recoveryForm->isRecovery);
        $this->assertEquals(
            Rock::$app->template->getAllPlaceholders(false, true),
            [
                'e_captcha' =>
                    "captcha must be valid"
            ]
        );
    }


    protected function signUp($email)
    {
        $post = [
            'email' => $email,
            'username' => 'Chuck',
            'password' => '123456',
            'password_confirm' => '123456',
            Rock::$app->token->csrfParam => function(){ return Rock::$app->token->create((new SignupForm())->formName());},
            'captcha' => '12345'
        ];
        Rock::$app->session->setFlash('captcha', '12345');
        $post[Rock::$app->token->csrfParam] = call_user_func($post[Rock::$app->token->csrfParam]);
        $signupForm = (new SignupForm());
        $_POST = [$signupForm->formName() => $post];
        $signupForm->load($_POST);
        $signupForm->validate();
        $this->assertTrue($signupForm->isSignup);
        $this->assertTrue((new UserMock())->activate($signupForm->getUsers()->token));
        $this->assertTrue(BaseUsers::existsByUsername('Chuck'));
    }


    public function testSuccess()
    {
        $email = 'chuck@gmail.com';

        $this->signUp($email);

        $post = [
            'email' => $email,
            Rock::$app->token->csrfParam => function(){ return Rock::$app->token->create((new RecoveryForm())->formName());},
            'captcha' => '12345'
        ];
        Rock::$app->session->setFlash('captcha', '12345');
        $post[Rock::$app->token->csrfParam] = call_user_func($post[Rock::$app->token->csrfParam]);
        $recoveryForm = (new RecoveryForm());
        $_POST = [$recoveryForm->formName() => $post];
        $recoveryForm->load($_POST);
        $recoveryForm->validate();
        $this->assertTrue($recoveryForm->isRecovery);

        $this->assertTrue($recoveryForm->getUsers()->validatePassword($recoveryForm->password));
        $this->assertTrue((bool)BaseUsers::deleteByUsername('Chuck'));
    }
}
 