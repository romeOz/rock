<?php

namespace rockunit\core\validation;

use rock\base\Model;
use rock\i18n\i18n;
use rock\Rock;
use rock\validation\Validation;

/**
 * @group base
 */
class ValidationTest extends \PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        static::tearDownAfterClass();
    }

    public static function tearDownAfterClass()
    {
        Rock::$app->template->removeAllPlaceholders();
        Rock::$app->template->removeAllPlaceholders(true);
        Rock::$app->validation->removeAllGroups();
        Validation::setLocale(i18n::EN);
    }


    public function testAddPlaceholderTrue()
    {
        $this->assertFalse(Rock::$app->validation->numeric()->setPlaceholders('test')->validate('foo'));
        $this->assertTrue(Rock::$app->template->hasPlaceholder('test', true));
    }


    public function testAddPlaceholderFalse()
    {
        $this->assertTrue(Rock::$app->validation->numeric()->setPlaceholders('test')->validate(5));
        $this->assertFalse(Rock::$app->template->hasPlaceholder('test'));
    }

    public function testAddGroup()
    {
        Rock::$app->validation->addGroup(
            'validNumeric',
            function () {
                return Rock::$app->validation->numeric();
            }
        );
        $this->assertFalse(Rock::$app->validation->validNumeric()->setPlaceholders('test')->validate('foo'));
        $this->assertTrue(Rock::$app->template->hasPlaceholder('test',true));
    }


    public function testLocale()
    {
        Validation::setLocale(i18n::RU);
        $this->assertFalse(Rock::$app->validation->numeric()->setPlaceholders('test')->validate('foo'));
        $this->assertEquals(Rock::$app->template->getPlaceholder('test', false, true), '"foo" должно быть числом');
    }

    /**
     * @dataProvider providerDataTrue
     */
    public function testAllOfTrue($data)
    {
        $_POST['token_registration'] = Rock::$app->token->create();
        $this->assertTrue(
            Rock::$app->validation->notEmpty()->token('registration')->validate($_POST['token_registration'], 'token')
        );
        $this->assertTrue(
            Rock::$app->validation
                ->key('email', Validation::notEmpty()->length(4, 80, true)->email())
                ->key(
                    'username',
                    Validation::notEmpty()->length(3, 80, true)->regex('/^[\w\s\-\*\@\%\#\!\?\.\)\(\+\=\~\:]+$/iu')
                )
                ->key('password', Validation::notEmpty()->length(6, 20, true)->regex('/^[a-zA-Z\d\-\_\.]+$/i'))
                ->key('password_confirm', Validation::equals($data['password']))
                ->setPlaceholders(
                    [
                        'email.last' => 'e_email',
                        'username.last' => 'e_username',
                        'password.last' => 'e_password',
                        'password_confirm.last' => 'e_password_confirm'
                    ]
                )
                ->validate($data)
        );
    }


    public function providerDataTrue()
    {
        return [
            [
                [
                    'email' => 'foo@gmail.com',
                    'username' => 'foo',
                    'password' => '456789',
                    'password_confirm' => '456789',
                    'token_registration' => '123'
                ]
            ],
            [
                [
                    'email' => 'александр@кремлин.рф',
                    'username' => 'александр',
                    'password' => '456789',
                    'password_confirm' => '456789',
                    'token_registration' => '123'
                ]
            ]
        ];
    }


    /**
     * @dataProvider providerDataFalse
     */
    public function testAllOfFalse(array $data, array $placeholders, array $modelErrors)
    {
        $_SESSION[Rock::$app->token->csrfParam] = '1234';
        $this->assertFalse(
            Rock::$app->validation->notEmpty()->token('registration')->setMessages(['token' => 'token invalid'])
                ->setPlaceholders('token')->validate($data['token_reg'])
        );
        $model = new Model();
        $validator = Rock::$app->validation
            ->setModel($model)
            ->allOf(
                Validation::key('email', Validation::notEmpty()->length(10, 80, true)->email()),
                Validation::key(
                    'username',
                    Validation::notEmpty()->length(3, 80, true)->regex('/^[\w\s\-\*\@\%\#\!\?\.\)\(\+\=\~\:]+$/iu')
                ),
                Validation::key(
                    'password',
                    Validation::notEmpty()->length(6, 20, true)->regex('/^[\w\-\.]+$/i')
                ),
                Validation::key('password_confirm', Validation::confirm($data['password'], true)->setName('passwords'))
            )
            ->setMessages(['email' => ['email' => 'email invalid']])
            ->setPlaceholders(
                [
                    'email',
                    'email.length' => 'e_email_length',
                    'username.last' => 'e_username',
                    'password.last',
                    'password_confirm.last' => 'e_password_confirm'
                ]
            );
        $this->assertFalse(
            $validator
                ->validate($data)
        );
        $this->assertEquals(Rock::$app->template->getAllPlaceholders(false, true), $placeholders);
        $this->assertEquals($model->getErrors(), $modelErrors);
    }

    public function providerDataFalse()
    {
        return [
            [
                [
                    'email' => 'foo@gmail',
                    'username' => 'foo',
                    'password' => '456789',
                    'password_confirm' => '45678',
                    'token_reg' => '123'
                ],
                [
                    'token' => 'token invalid',
                    'e_email' =>
                        [
                            'email' => 'email invalid',
                            'length' => '"foo@gmail" must have a length between 10 and 80',
                        ],
                    'e_email_length' => '"foo@gmail" must have a length between 10 and 80',
                    'e_password_confirm' => 'values must be equals',
                ],
                array(
                    'email' =>
                        array(
                            'length' => '"foo@gmail" must have a length between 10 and 80',
                            'email' => 'email invalid',
                        ),
                    'password_confirm' =>
                        array(
                            'confirm' => 'values must be equals',
                        ),
                )
            ]
        ];
    }


    /**
     * @dataProvider providerDataFalse
     */
    public function testOneOfTrue($data)
    {
        $this->assertTrue(
            Rock::$app->validation
                ->oneOf(
                    Validation::key('email', Validation::notEmpty()->length(4, 80, true)->email()),
                    Validation::key(
                        'username',
                        Validation::notEmpty()->length(3, 80, true)->regex('/^[\w\s\-\*\@\%\#\!\?\.\)\(\+\=\~\:]+$/iu')
                    ),
                    Validation::key(
                        'password',
                        Validation::notEmpty()->length(6, 20, true)->regex('/^[a-zA-Z\d\-\_\.]+$/i')
                    ),
                    Validation::key('password_confirm', Validation::confirm($data['password']))
                )
                ->setPlaceholders(
                    [
                        'email.last' => 'e_email',
                        'username.last' => 'e_username',
                        'password.last' => 'e_password',
                        'password_confirm.last' => 'e_password_confirm'
                    ]
                )
                ->validate($data)
        );
    }


    public function testOneOfFalse()
    {
        $data = [
            'email' => 'foo@gmail',
            'username' => 'f',
            'password' => '45',
            'password_confirm' => '45678',
        ];
        $model = new Model();
        $this->assertFalse(
            Rock::$app->validation
                ->oneOf(
                    Validation::key('email', Validation::notEmpty()->length(4, 80, true)->email()),
                    Validation::key(
                        'username',
                        Validation::notEmpty()->length(3, 80, true)->regex('/^[\w\s\-\*\@\%\#\!\?\.\)\(\+\=\~\:]+$/iu')
                    ),
                    Validation::key(
                        'password',
                        Validation::notEmpty()->length(6, 20, true)->regex('/^[a-zA-Z\d\-\_\.]+$/i')
                    ),
                    Validation::key('password_confirm', Validation::confirm($data['password']))
                )
                ->setModel($model)
                ->setMessages(['password' => ['length' => 'password invalid']])
                ->setPlaceholders(
                    [
                        'email.last' => 'e_email',
                        'username.last' => 'e_username',
                        'password.last' => 'e_password',
                        'password_confirm.last' => 'e_password_confirm'
                    ]
                )
                ->validate($data)
        );
        $this->assertEquals(
            Rock::$app->template->getAllPlaceholders(false, true),
            array(
                'e_email' => '"foo@gmail" must be valid email',
                'e_username' => '"f" must have a length between 3 and 80',
                'e_password' => 'password invalid',
                'e_password_confirm' => 'values must be equals',
            )
        );
        $this->assertEquals(
            $model->getErrors(),
            array(
                'email' =>
                    array(
                        'email' => '"foo@gmail" must be valid email',
                    ),
                'username' =>
                    array(
                        'length' => '"f" must have a length between 3 and 80',
                    ),
                'password' =>
                    array(
                        'length' => 'password invalid',
                    ),
                'password_confirm' =>
                    array(
                        'confirm' => 'values must be equals',
                    ),
            )
        );
    }


    public function testNot()
    {
        $model = new Model();
        $data = [
            'email' => 'foo@gmail',
            'username' => 'foo',
            'password' => '456789',
            'password_confirm' => '456789',
            'token_reg' => '123'
        ];
        $this->assertFalse(
            Rock::$app->validation
                ->allOf(
                    Validation::key('email', Validation::not(Validation::notEmpty()->length(10, 80, true)->email())),
                    Validation::key(
                        'username',
                        Validation::notEmpty()->length(3, 80, true)->regex('/^[\w\s\-\*\@\%\#\!\?\.\)\(\+\=\~\:]+$/iu')
                    ),
                    Validation::key(
                        'password',
                        Validation::notEmpty()->length(6, 20, true)->regex('/^[\w\-\.]+$/i')
                    ),
                    Validation::key(
                        'password_confirm',
                        Validation::confirm($data['password'], true)->setName('passwords')
                    )
                )
                ->setModel($model)/*->setPlaceholders('e_ff')*/
                ->setPlaceholders(['email.last' => 'e_email',])
                ->validate($data)
        );
        $this->assertEquals(
            $model->getErrors(),
            array(
                'email' =>
                    array(
                        'notEmpty' =>
                            "the value must be empty"
                    )
            )
        );
        $this->assertEquals(
            Rock::$app->template->getAllPlaceholders(false, true),
            array (
                'e_email' => 'the value must be empty',
            )
        );
    }




    public function testStaticCreateShouldReturnNewValidator()
    {
        $this->assertInstanceOf('rock\validation\Validation', Validation::create());
    }

    public function testInvalidRuleClassShouldThrowComponentException()
    {
        $this->setExpectedException('rock\validation\exceptions\ComponentException');
        Validation::iDoNotExistSoIShouldThrowException();
    }
    public function testSetTemplateWithSingleValidatorShouldUseTemplateAsMainMessage()
    {
        try {
            Validation::callback('is_int')->setTemplate('{{name}} is not tasty')->assert('something');
        } catch (\Exception $e) {
            $this->assertEquals('"something" is not tasty', $e->getMainMessage());
        }
    }
    public function testSetTemplateWithMultipleValidatorsShouldUseTemplateAsMainMessage()
    {
        try {
            Validation::callback('is_int')->between(1,2)->setTemplate('{{name}} is not tasty')->assert('something');
        } catch (\Exception $e) {
            $this->assertEquals('"something" is not tasty', $e->getMainMessage());
        }
    }
    public function testSetTemplateWithMultipleValidatorsShouldUseTemplateAsFullMessage()
    {
        try {
            Validation::callback('is_string')->between(1,2)->setTemplate('{{name}} is not tasty')->assert('something');
        } catch (\Exception $e) {
            $this->assertEquals('\-"something" is not tasty
  \-"something" must be greater than 1', $e->getFullMessage());
        }
    }
    public function testGetFullMessageShouldIncludeAllValidationMessagesInAChain()
    {
        try {
            Validation::string()->length(1,15)->assert('');
        } catch (\Exception $e) {
            $this->assertEquals('\-These rules must pass for ""
  \-"" must have a length between 1 and 15', $e->getFullMessage());
        }
    }

    public function testNotShouldWorkByBuilder()
    {
        $this->assertFalse(Validation::not(Validation::int())->validate(10));
    }
    public function testCountryCode()
    {
        $this->assertTrue(Validation::countryCode()->validate('BR'));
    }
    public function testAlwaysValid()
    {
        $this->assertTrue(Validation::alwaysValid()->validate('sojdnfjsdnfojsdnfos dfsdofj sodjf '));
    }
    public function testAlwaysInvalid()
    {
        $this->assertFalse(Validation::alwaysInvalid()->validate('sojdnfjsdnfojsdnfos dfsdofj sodjf '));
    }

    public function testIssue85FindMessagesShouldNotTriggerCatchableFatalError()
    {
        $usernameValidator = Validation::alnum('_')->length(1,15)->noWhitespace();
        try {
            $usernameValidator->assert('really messed up screen#name');
        } catch (\InvalidArgumentException $e) {
            $e->findMessages(array('alnum', 'length', 'noWhitespace'));
        }
    }

    public function testKeysAsValidatorNames()
    {
        try {
            Validation::key('username', Validation::length(1,32))
                ->key('birthdate', Validation::date())
                ->setName("User Subscription Form")
                ->assert(array('username' => '', 'birthdate' => ''));
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals('\-These rules must pass for User Subscription Form
  |-Key username must be valid
  | \-"" must have a length between 1 and 32
  \-Key birthdate must be valid
    \-"" must be a valid date', $e->getFullMessage());
        }
    }
}
 