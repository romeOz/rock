<?php

namespace rockunit\core\forms\models;


use apps\common\models\forms\BaseSignupForm;
use rock\event\Event;
use rock\Rock;
use rockunit\core\db\models\BaseUsers;

class SignupForm extends BaseSignupForm
{

//    public function rules()
//    {
//        return [
//            [
//                self::RULE_VALIDATE, '_csrf', 'validateCSRF', 'one'
//            ],
//            [
//                self::RULE_SANITIZE, ['email', 'username', 'password', 'password_confirm', 'captcha'], 'trim'
//            ],
//            [
//                self::RULE_VALIDATE, ['email', 'username', 'password', 'password_confirm', 'captcha'], 'required',
//            ],
//            [
//                self::RULE_VALIDATE, 'email', 'length' => [4, 80, true], 'email'
//            ],
//            [
//                self::RULE_VALIDATE, 'username', 'length' => [3, 80, true], 'regex' => ['/^[\w\s\-\*\@\%\#\!\?\.\)\(\+\=\~\:]+$/iu']
//            ],
//            [
//                self::RULE_VALIDATE, 'password', 'length' => [6, 20, true], 'regex' => ['/^[a-z\d\-\_\.]+$/i']
//            ],
//            [
//                self::RULE_VALIDATE, 'password_confirm', 'confirm' => [$this->password]
//            ],
//            [
//                self::RULE_VALIDATE, 'captcha', 'captcha' => [$this->Rock->captcha->getSession()]
//            ],
//            [
//                self::RULE_SANITIZE, 'email', 'lowercase'
//            ],
//            [
//                self::RULE_SANITIZE, ['email', 'username', 'password', 'password_confirm', 'captcha'], 'removeTags'
//            ],
//            [
//                self::RULE_VALIDATE, 'username', 'validateExistsUser'
//            ],
//        ];
//    }

//    public function rules()
//    {
//        //$timestamp = time();
//        return [
//            [
//                self::RULE_VALIDATION,
//                function(array $attributes){
//
//                    if ($this->Rock->validation
//                            ->notEmpty()
//                            ->token($this->formName())
//                            ->setName(Rock::t('token'))
//                            ->setModel($this)
//                            ->setPlaceholders('e_signup')
//                            ->validate($attributes[$this->Rock->csrf->csrfParam]) === false
//                    ) {
//                        return false;
//                    }
//                    if ($this->Rock->validation
//                            ->key(
//                                'email',
//                                Validation::notEmpty()
//                                    ->length(4, 80, true)
//                                    ->email()
//                            //->setName($this->Rock->i18n->get('email'))
//                            )
//                            ->key(
//                                'username',
//                                Validation::notEmpty()
//                                    ->length(3, 80, true)
//                                    ->regex('/^[\w\s\-\*\@\%\#\!\?\.\)\(\+\=\~\:]+$/iu')
//                            //->setName($this->Rock->i18n->get('username'))
//                            )
//                            ->key(
//                                'password',
//                                Validation::notEmpty()
//                                    ->length(6, 20, true)
//                                    ->regex('/^[\\w\\d\-\.]+$/i')
//                                    ->setName(Rock::t('password'))
//                            )
//                            ->key(
//                                'password_confirm',
//                                Validation::notEmpty()
//                                    ->confirm(Helper::getValue($attributes['password']), true)
//                            //->setName(Rock::t('passwords'))
//                            )
//                            ->key(
//                                'captcha',
//                                Validation::notEmpty()
//                                    ->captcha($this->Rock->captcha->getSession(), true)
//                            //->setName($this->Rock->i18n->get('captcha'))
//                            )
//                            ->setModel($this)
//                            ->setPlaceholders(
//                                [
//                                    'email.first',
//                                    'username.first' => 'e_username',
//                                    'password.first' => 'e_password',
//                                    'password_confirm.first' => 'e_password_confirm',
//                                    'captcha.first' => 'e_captcha'
//                                ]
//                            )
//                            ->validate($attributes) === false) {
//                        return false;
//                    }
//
//                    if (BaseUsers::existsByUsernameOrEmail($attributes['email'], $attributes['username']) === true) {
//                        $this->Rock->template->addPlaceholder('e_signup', Rock::t('existsUsernameOrEmail'), true);
//                        return false;
//                    }
//                    return true;
//
//                }],
//            [
//                self::RULE_BEFORE_FILTERS,
//                [
//                    Sanitize::ANY => [Sanitize::STRIP_TAGS, 'trim'],
//                    'email' => [(object)['mb_strtolower', [Rock::$app->charset]]],
//                ],
//
//            ],
//        ];
//    }

    public function afterSignup()
    {
        if (!$users = BaseUsers::create($this->getAttributes())) {
            $this->addErrorAsPlaceholder(Rock::t('failSignup'), 'e_signup');
            return false;
        }
        $this->users = $users;
        $this->isSignup = true;
        $result = $users->toArray();
        if ($this->trigger(self::EVENT_AFTER_SIGNUP, Event::AFTER)->after(null, $result) === false) {
            return false;
        }


        return true;
    }
} 