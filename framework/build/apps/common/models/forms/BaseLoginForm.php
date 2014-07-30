<?php

namespace apps\common\models\forms;


use apps\common\models\users\BaseUsers;
use rock\base\Model;
use rock\event\Event;
use rock\helpers\ArrayHelper;
use rock\helpers\Sanitize;
use rock\Rock;
use rock\validation\Validation;

class BaseLoginForm extends Model
{
    const EVENT_BEFORE_LOGIN = 'beforeLogin';
    const EVENT_AFTER_LOGIN = 'afterLogin';


    /** @var  string */
    public $email;
    /** @var  string */
    public $password;
    /** @var  string */
    public $csrf;

    public $redirectUrl;

    public $isLogin = false;


    public function rules()
    {
        //$timestamp = time();
        return [
            [
                self::RULE_VALIDATION,
                function(array $attributes){

                    if ($this->Rock->validation
                            ->notEmpty()
                            ->token($this->formName())
                            ->setName(Rock::t('token'))
                            ->setPlaceholders('e_login')
                            ->setModel($this)
                            ->validate($attributes[$this->Rock->token->csrfPrefix]) === false
                    ) {
                        return false;
                    }
                    if ($this->Rock->validation
                            ->key(
                                'email',
                                Validation::notEmpty()
                                    ->length(4, 80, true)
                                    ->email()
                            //->setName($this->Rock->i18n->get('email'))
                            )
                            ->key(
                                'password',
                                Validation::notEmpty()
                                    ->length(6, 20, true)
                                    ->regex('/^[a-z\d\-\_\.]+$/i')
                                    ->setName(Rock::t('password'))
                            )
                            ->setModel($this)
                            ->setPlaceholders(['email.first', 'password.first'])
                            ->validate($attributes) === false) {
                        return false;
                    }



                    if (!$this->validatePassword()) {
                        return false;
                    }

                    return $this->validateStatus();

                }],
            [
                self::RULE_BEFORE_FILTERS,
                [
                    Sanitize::ANY => [Sanitize::STRIP_TAGS, 'trim'],
                    'email' => [(object)['mb_strtolower', [Rock::$app->charset]]],
                ],

            ],
        ];
    }

    public function safeAttributes()
    {
        return ['email', 'password', $this->Rock->token->csrfPrefix];
    }


    public function attributeLabels()
    {
        return [
            'email' => Rock::t('email'),
            'password'=> Rock::t('password')
        ];
    }


    /**
     * Validates the password.
     * This method serves as the inline validation for password.
     */
    public function validatePassword()
    {
        if (!$user = $this->getUsers()) {
            return false;
        }

        if (!$user->validatePassword($this->password)) {
            $this->Rock->template->addPlaceholder('e_login', Rock::t('invalidPasswordOrEmail'), true);
            return false;
        }
        return true;

    }

    public function validateStatus()
    {
        if (!$user = $this->getUsers()) {
            return false;
        }

        if ($user->status !== BaseUsers::STATUS_ACTIVE) {
            $this->Rock->template->addPlaceholder('e_login', Rock::t('notActivatedUser'), true);
            return false;
        }
        return true;
    }



    protected $_users;

    /**
     * Finds user by [[email]]
     *
     * @return BaseUsers
     */
    public function getUsers()
    {
        if (!isset($this->_users)) {
            if (!$this->_users = BaseUsers::find()->byEmail($this->email)->one()) {
                $this->Rock->template->addPlaceholder('e_login', Rock::t('notExistsUser'), true);
            }
        }

        return $this->_users;
    }


    public function validate(array $attributes = NULL, $clearErrors = true)
    {
        if (!$this->beforeLogin() || !parent::validate()) {
            return false;
        }

        $users = $this->getUsers();
        $users->login_last = $this->Rock->date->isoDatetime();
        if (!$users->save()) {
            $this->Rock->template->addPlaceholder('e_login', Rock::t('failAuth'), true);
            return false;
        }

        $this->isLogin = true;
        $data = $users->toArray();
        $this->Rock->user->addMulti(ArrayHelper::intersectByKeys($data, ['id', 'username', 'url']));
        $this->Rock->user->login();

        $this->afterLogin($data);

        //$this->redirect();
        return true;
    }


    /**
     * @param string|null $url
     */
    public function redirect($url = null)
    {
        if (!$this->isLogin) {
            return;
        }
        $response = $this->Rock->response;
        if (!isset($url) && isset($this->redirectUrl)) {
            $url = $this->redirectUrl;
        }
        if (!isset($url)) {
            $response->refresh()->send(true);
        }
        $response->redirect($url)->send(true);
    }

    public function beforeLogin()
    {
        if ($this->trigger(self::EVENT_BEFORE_LOGIN)->before() === false) {
            return false;
        }

        return true;
    }

    public function afterLogin($result)
    {
        if ($this->trigger(self::EVENT_AFTER_LOGIN, Event::AFTER)->after(null, $result) === false) {
            return false;
        }
        return true;
    }
}