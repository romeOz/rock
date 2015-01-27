<?php

namespace apps\common\models\forms;


use apps\common\models\users\BaseUsers;
use rock\components\Model;
use rock\components\ModelEvent;
use rock\csrf\CSRF;
use rock\date\DateTime;
use rock\di\Container;
use rock\helpers\ArrayHelper;
use rock\i18n\i18n;
use rock\response\Response;
use rock\user\User;
use rock\validate\Validate;

class BaseLoginForm extends Model
{
    const EVENT_BEFORE_LOGIN = 'beforeLogin';
    const EVENT_AFTER_LOGIN = 'afterLogin';


    /** @var  string */
    public $email;
    /** @var  string */
    public $password;
    /** @var  string */
    public $_csrf;

    public $redirectUrl;
    public $isLogged = false;

    /** @var  CSRF */
    protected $_csrfInstance;
    /** @var  User */
    protected $_user;
    /** @var  Response */
    protected $_response;

    public function init()
    {
        parent::init();

        $this->_csrfInstance = Container::load('csrf');
        $this->_user = Container::load('user');
        $this->_response = Container::load('response');
    }


    public function rules()
    {
        return [
            [
                self::RULE_VALIDATE, '_csrf', 'validateCSRF', 'one'
            ],
            [
                self::RULE_SANITIZE, ['email', 'password'], 'trim'
            ],
            [
                self::RULE_VALIDATE, ['email', 'password'], 'required',
            ],
            [
                self::RULE_VALIDATE, 'email', 'length' => [4, 80, true], 'email'
            ],
            [
                self::RULE_VALIDATE, 'password', 'length' => [6, 20, true], 'regex' => ['/^[a-z\d\-\_\.]+$/i']
            ],
            [
                self::RULE_SANITIZE, 'email', 'lowercase'
            ],
            [
                self::RULE_SANITIZE, ['email', 'password'], 'removeTags'
            ],
            [
                self::RULE_VALIDATE, 'password', 'validatePassword', 'validateStatus'
            ],
        ];
    }

    public function safeAttributes()
    {
        return ['email', 'password', $this->_csrfInstance->csrfParam];
    }


    public function attributeLabels()
    {
        return [
            'email' => i18n::t('email'),
            'password'=> i18n::t('password')
        ];
    }


    protected $_users;

    /**
     * Finds user by `email`
     *
     * @return BaseUsers
     */
    public function getUsers()
    {
        if (!isset($this->_users)) {
            if (!$this->_users = BaseUsers::findOneByEmail($this->email, null, false)) {
                $this->addErrorAsPlaceholder(i18n::t('notExistsUser'), 'e_login');
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
        $users->login_last = DateTime::set()->isoDatetime();
        if (!$users->save()) {
            $this->addErrorAsPlaceholder(i18n::t('failLogin'), 'e_login');
            return false;
        }

        $this->isLogged = true;
        $data = $users->toArray();
        $this->_user->addMulti(ArrayHelper::intersectByKeys($data, ['id', 'username', 'url']));
        $this->_user->login();

        $this->afterLogin($data);

        //$this->redirect();
        return true;
    }


    /**
     * @param string|null $url
     */
    public function redirect($url = null)
    {
        if (!$this->isLogged) {
            return;
        }
        if (!isset($url) && isset($this->redirectUrl)) {
            $url = $this->redirectUrl;
        }
        if (!isset($url)) {
            $this->_response->refresh()->send(true);
        }
        $this->_response->redirect($url)->send(true);
    }

    public function beforeLogin()
    {
        $event = new ModelEvent();
        $this->trigger(self::EVENT_BEFORE_LOGIN, $event);
        return $event->isValid;
    }

    public function afterLogin($result)
    {
        $event = new ModelEvent();
        $event->result = $result;
        $this->trigger(self::EVENT_AFTER_LOGIN, $event);
    }



    /**
     * Validates the password.
     * This method serves as the inline validation for password.
     */
    protected function validatePassword($password)
    {
        if ($this->hasErrors()) {
            return true;
        }
        if (!$user = $this->getUsers()) {
            return false;
        }
        if (!$user->validatePassword($password)) {
            $this->addErrorAsPlaceholder(i18n::t('invalidPasswordOrEmail'), 'e_login');
            return false;
        }
        return true;

    }

    protected function validateCSRF($input)
    {
        $v = Validate::required()->csrf()->placeholders(['name' => 'CSRF-token']);
        if (!$v->validate($input)) {
            $this->addErrorAsPlaceholder($v->getFirstError(), 'e_login');
            return false;
        }

        return true;
    }

    protected function validateStatus()
    {
        if ($this->hasErrors()) {
            return true;
        }
        if (!$user = $this->getUsers()) {
            return false;
        }

        if ($user->status !== BaseUsers::STATUS_ACTIVE) {
            $this->addErrorAsPlaceholder(i18n::t('notActivatedUser'), 'e_login');
            return false;
        }
        return true;
    }

}