<?php

namespace rockunit\core\forms;

use rock\i18n\i18n;
use rock\Rock;
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
        Rock::$app->language = i18n::EN;
    }

    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();
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
    public function testFail(array $post, array $errors)
    {
        $post[Rock::$app->csrf->csrfParam] = call_user_func($post[Rock::$app->csrf->csrfParam]);
        $model = new RecoveryForm();
        $_POST = [$model->formName() => $post];
        $model->load($_POST);
        $this->assertFalse($model->validate());
        $this->assertFalse($model->isRecovery);
        $this->assertEquals($errors, $model->getErrors());
    }

    public function providerFail()
    {
        return [
            [
                [
                    'email' => '        fooGMAIL.ru  ',
                    Rock::$app->csrf->csrfParam => function () {
                        return Rock::$app->csrf->create((new RecoveryForm())->formName());
                    },
                    'captcha' => '12345'
                ],
                [
                    'email' =>
                        [
                           'e-mail must be valid',
                        ],
                    'captcha' =>
                        [
                            'captcha must be valid',
                        ],
                ]
            ],
            [
                [
                    'email' => '',
                    Rock::$app->csrf->csrfParam => function () {
                        return Rock::$app->csrf->create((new RecoveryForm())->formName());
                    },
                    'captcha' => ''
                ],
                array(
                    'email' =>
                        [
                           'e-mail must not be empty',
                        ],
                    'captcha' =>
                        [
                            'captcha must not be empty',
                        ],
                )
            ],
            [
                [
                    'email' => 'foogmail.ru',
                    Rock::$app->csrf->csrfParam => function () {
                        return '';
                    },
                    'captcha' => '12345'
                ],
                array(
                    'e_recovery' =>
                        [
                            'CSRF-token must not be empty',
                        ],
                )
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
            Rock::$app->csrf->csrfParam => function () {
                return Rock::$app->csrf->create((new RecoveryForm())->formName());
            },
            'captcha' => '12345'
        ];
        Rock::$app->session->setFlash('captcha', '12345');
        $post[Rock::$app->csrf->csrfParam] = call_user_func($post[Rock::$app->csrf->csrfParam]);
        $model = new RecoveryForm();
        $_POST = [$model->formName() => $post];
        $model->load($_POST);
        $this->assertFalse($model->validate());
        $this->assertFalse($model->isRecovery);
        $this->assertEquals(
            array(
                'e_recovery' =>
                    array(
                        0 => 'Email is invalid.',
                    ),
            ),
            $model->getErrors()
        );
    }


    public function testCaptchaFail()
    {
        $post = [
            'email' => 'foo@gmail.ru',
            'username' => 'Jane',
            'password' => '123456',
            'password_confirm' => '123456',
            Rock::$app->csrf->csrfParam => function () {
                return Rock::$app->csrf->create((new RecoveryForm())->formName());
            },
            'captcha' => '1234'
        ];
        Rock::$app->session->setFlash('captcha', '12345');
        $post[Rock::$app->csrf->csrfParam] = call_user_func($post[Rock::$app->csrf->csrfParam]);
        $model = (new RecoveryForm());
        $_POST = [$model->formName() => $post];
        $model->load($_POST);
        $this->assertFalse($model->validate());
        $this->assertFalse($model->isRecovery);
        $this->assertEquals(
            [
                'captcha' =>
                    [
                        'captcha must be valid',
                    ],
            ],
            $model->getErrors()
        );
    }

    public function testSuccess()
    {
        $email = 'chuck@gmail.com';
        $this->signUp($email);
        $post = [
            'email' => $email,
            Rock::$app->csrf->csrfParam => function () {
                return Rock::$app->csrf->create((new RecoveryForm())->formName());
            },
            'captcha' => '12345'
        ];
        Rock::$app->session->setFlash('captcha', '12345');
        $post[Rock::$app->csrf->csrfParam] = call_user_func($post[Rock::$app->csrf->csrfParam]);
        $model = new RecoveryForm();
        $_POST = [$model->formName() => $post];
        $model->load($_POST);
        $this->assertTrue($model->validate());
        $this->assertTrue($model->isRecovery);
        $this->assertTrue($model->getUsers()->validatePassword($model->password));
        $this->assertTrue((bool)BaseUsers::deleteByUsername('Chuck'));
    }

    protected function signUp($email)
    {
        $post = [
            'email' => $email,
            'username' => 'Chuck',
            'password' => '123456',
            'password_confirm' => '123456',
            Rock::$app->csrf->csrfParam => function () {
                return Rock::$app->csrf->create((new SignupForm())->formName());
            },
            'captcha' => '12345'
        ];
        Rock::$app->session->setFlash('captcha', '12345');
        $post[Rock::$app->csrf->csrfParam] = call_user_func($post[Rock::$app->csrf->csrfParam]);
        $model = new SignupForm();
        $_POST = [$model->formName() => $post];
        $model->load($_POST);
        $this->assertTrue($model->validate());
        $this->assertTrue($model->isSignup);
        $this->assertTrue((new UserMock())->activate($model->getUsers()->token));
        $this->assertTrue(BaseUsers::existsByUsername('Chuck'));
    }
}
 