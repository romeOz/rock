<?php

namespace rockunit\core\forms;

use rock\csrf\CSRF;
use rock\di\Container;
use rock\Rock;
use rock\template\Template;
use rockunit\common\CommonTestTrait;
use rockunit\core\db\DatabaseTestCase;
use rockunit\core\db\models\ActiveRecord;
use rockunit\core\db\models\BaseUsers;
use rockunit\core\forms\models\RecoveryFormMock;
use rockunit\core\forms\models\SignupFormMock;

/**
 * @group forms
 * @group db
 */
class RecoveryFormTest extends DatabaseTestCase
{
    use  CommonTestTrait;

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
        static::activeSession();
    }

    public function tearDown()
    {
        parent::tearDown();
        static::sessionDown();
        static::activeSession(false);
    }

    /**
     * @dataProvider providerFail
     * @param array $post
     * @param array $errors
     * @throws \rock\di\ContainerException
     */
    public function testFail(array $post, array $errors)
    {
        /** @var CSRF $csrf */
        $csrf = Container::load(CSRF::className());
        $post[$csrf->csrfParam] = call_user_func($post[$csrf->csrfParam]);
        $model = new RecoveryFormMock();
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
                        return Rock::$app->csrf->get();
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
                        return Rock::$app->csrf->get();
                    },
                    'captcha' => ''
                ],
                [
                    'email' =>
                        [
                           'e-mail must not be empty',
                        ],
                    'captcha' =>
                        [
                            'captcha must not be empty',
                        ],
                ]
            ],
            [
                [
                    'email' => 'foogmail.ru',
                    Rock::$app->csrf->csrfParam => function () {
                        return '';
                    },
                    'captcha' => '12345'
                ],
                [
                    'e_recovery' =>
                        [
                            'CSRF-token must not be empty',
                        ],
                ]
            ],
        ];
    }

    public function testExistsUserByEmailFail()
    {
        /** @var CSRF $csrf */
        $csrf = Container::load(CSRF::className());
        $post = [
            'email' => 'chuck@hotmail.com',
            'username' => 'Chuck',
            'password' => '123456',
            'password_confirm' => '123456',
            $csrf->csrfParam => $csrf->get(),
            'captcha' => '12345'
        ];
        static::getSession()->setFlash('captcha', '12345');
        $template = new Template();
        $model = new RecoveryFormMock();
        $model->setTemplate($template);
        $_POST = [$model->formName() => $post];
        $model->load($_POST);
        $this->assertFalse($model->validate());
        $this->assertFalse($model->isRecovery);
        $expected = [
            'e_recovery' =>
                [
                    0 => 'Email is invalid.',
                ],
        ];
        $this->assertSame($expected, $model->getErrors());
        $this->assertSame($expected, $template->getAllPlaceholders('$root'));
    }


    public function testCaptchaFail()
    {
        /** @var CSRF $csrf */
        $csrf = Container::load(CSRF::className());
        $post = [
            'email' => 'foo@gmail.ru',
            'username' => 'Jane',
            'password' => '123456',
            'password_confirm' => '123456',
            $csrf->csrfParam =>$csrf->get(),
            'captcha' => '1234'
        ];
        static::getSession()->setFlash('captcha', '12345');
        $model = (new RecoveryFormMock());
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
        /** @var CSRF $csrf */
        $csrf = Container::load(CSRF::className());

        $email = 'chuck@gmail.com';
        $this->signUp($email);
        $post = [
            'email' => $email,
            $csrf->csrfParam => $csrf->get(),
            'captcha' => '12345'
        ];
        static::getSession()->setFlash('captcha', '12345');
        $model = new RecoveryFormMock();
        $_POST = [$model->formName() => $post];
        $model->load($_POST);
        $this->assertTrue($model->validate());
        $this->assertTrue($model->isRecovery);
        $this->assertTrue($model->getUsers()->validatePassword($model->password));
        $this->assertTrue((bool)BaseUsers::deleteByUsername('Chuck'));
    }

    protected function signUp($email)
    {
        /** @var CSRF $csrf */
        $csrf = Container::load(CSRF::className());

        $post = [
            'email' => $email,
            'username' => 'Chuck',
            'password' => '123456',
            'password_confirm' => '123456',
            $csrf->csrfParam => $csrf->get(),
            'captcha' => '12345'
        ];
        static::getSession()->setFlash('captcha', '12345');
        $model = new SignupFormMock();
        $_POST = [$model->formName() => $post];
        $model->load($_POST);
        $this->assertTrue($model->validate());
        $this->assertTrue($model->isSignup);
        $this->assertNotEmpty(BaseUsers::activate($model->getUsers()->token));
        $this->assertTrue(BaseUsers::existsByUsername('Chuck'));
    }
}
 