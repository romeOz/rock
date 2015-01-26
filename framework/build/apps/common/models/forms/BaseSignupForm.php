<?php

namespace apps\common\models\forms;

use apps\common\models\users\BaseUsers;
use rock\base\BaseException;
use rock\captcha\Captcha;
use rock\components\Model;
use rock\csrf\CSRF;
use rock\date\DateTime;
use rock\db\Connection;
use rock\di\Container;
use rock\events\ModelEvent;
use rock\helpers\ArrayHelper;
use rock\helpers\Helper;
use rock\helpers\StringHelper;
use rock\i18n\i18n;
use rock\log\Log;
use rock\mail\Mail;
use rock\response\Response;
use rock\session\Session;
use rock\template\Template;
use rock\url\Url;
use rock\user\User;
use rock\validate\Validate;

class BaseSignupForm extends Model
{
    const EVENT_BEFORE_SIGNUP = 'beforeSignup';
    const EVENT_AFTER_SIGNUP = 'afterSignup';


    public $email;
    public $username;
    public $_csrf;
    public $password_confirm;
    public $captcha;
    public $password;

    public $emailBodyTpl = '@common.views/email/{lang}/activate';
    public $redirectUrl;
    public $activateUrl = '/activation.html';
    public $defaultStatus = BaseUsers::STATUS_NOT_ACTIVE;
    public $generateToken = true;

    public $isSignup = false;

    /** @var Connection */
    protected $connection;

    /** @var  CSRF */
    protected $_csrfInstance;
    /** @var  Response */
    protected $_response;
    /** @var  Captcha */
    protected $_captcha;
    /** @var  Template */
    protected $_template;
    /** @var  Mail */
    protected $_mail;
    /** @var  Session */
    protected $_session;
    /** @var  User */
    protected $_user;

    public function init()
    {
        parent::init();

        $this->_csrfInstance = Container::load('csrf');
        $this->_response = Container::load('response');
        $this->_captcha = Container::load('captcha');
        $this->_template = Container::load('template');
        $this->_mail = Container::load('mail');
        $this->_session = Container::load('session');
        $this->_user = Container::load('user');
    }

    public function rules()
    {
        return [
            [
                self::RULE_VALIDATE, '_csrf', 'validateCSRF', 'one'
            ],
            [
                self::RULE_SANITIZE, ['email', 'username', 'password', 'password_confirm', 'captcha'], 'trim'
            ],
            [
                self::RULE_VALIDATE, ['email', 'username', 'password', 'password_confirm', 'captcha'], 'required',
            ],
            [
                self::RULE_VALIDATE, 'email', 'length' => [4, 80, true], 'email'
            ],
            [
                self::RULE_VALIDATE, 'username', 'length' => [3, 80, true], 'regex' => ['/^[\w\s\-\*\@\%\#\!\?\.\)\(\+\=\~\:]+$/iu']
            ],
            [
                self::RULE_VALIDATE, 'password', 'length' => [6, 20, true], 'regex' => ['/^[a-z\d\-\_\.]+$/i']
            ],
            [
                self::RULE_VALIDATE, 'password_confirm', 'confirm' => [$this->password]
            ],
            [
                self::RULE_VALIDATE, 'captcha', 'captcha' => [$this->_captcha->getSession()]
            ],
            [
                self::RULE_SANITIZE, 'email', 'lowercase'
            ],
            [
                self::RULE_SANITIZE, ['email', 'username', 'password', 'password_confirm', 'captcha'], 'removeTags'
            ],
            [
                self::RULE_VALIDATE, 'username', 'validateExistsUser'
            ],
        ];
    }


    public function safeAttributes()
    {
        return ['email', 'username', 'password', 'password_confirm', 'captcha', $this->_csrfInstance->csrfParam];
    }

    public function attributeLabels()
    {
        return [
            'email'=> i18n::t('email'),
            'password'=> i18n::t('password'),
            'username'=> i18n::t('username'),
            'password_confirm'=> i18n::t('confirmPassword'),
            'captcha'=> i18n::t('captcha'),
        ];
    }

    /**
     * @var BaseUsers
     */
    protected $users;

    public function validate(array $attributes = NULL, $clearErrors = true)
    {
        if (!$this->beforeSignup() || !parent::validate()) {
            return false;
        }

        return $this->afterSignup();
    }

    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @return BaseUsers
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * @param string|null $url
     */
    public function redirect($url = null)
    {
        if ($this->isSignup === false) {
            return;
        }

        $this->_session->setFlash('successSignup', ['email' => StringHelper::replaceRandChars($this->email)]);

        $response = $this->_response;
        if (!isset($url) && isset($this->redirectUrl)) {
            $url = $this->redirectUrl;
        }
        if (!isset($url)) {
            $response->refresh()->send(true);
        }
        $response->redirect($url)->send(true);
    }

    protected function prepareBody(array $data, $chunk)
    {
        $name = $data['username'];
        if (isset($data['firstname']) || isset($data['lastname'])) {
            $name = implode(' ', ArrayHelper::intersectByKeys($data, ['firstname', 'lastname']));
        }

        $data['fullname'] = $name;
        $data['password'] = $this->password;
        if ($this->generateToken) {
            $data['url'] = Url::set($this->activateUrl)
                ->addArgs(['token' => $data['token']])
                ->getAbsoluteUrl(true);
        }

        return $this->_template->getChunk($chunk, $data);
    }

    /**
     * @param null $subject
     * @param null $chunkBody
     */
    public function sendMail($subject = null, $chunkBody = null)
    {
        if ($this->isSignup === false) {
            return;
        }

        if (!isset($subject)) {
            $subject = i18n::t('subjectRegistration', ['site_name' => i18n::t('siteName')]);
        }

        $body = $this->prepareBody($this->getUsers()->toArray(['username', 'email','token']), Helper::getValue($chunkBody, $this->emailBodyTpl));

        try {
            $this->_mail
                ->address($this->email)
                ->subject($subject)
                ->body($body)
                ->send();
        } catch (\Exception $e) {
            $this->addErrorAsPlaceholder(i18n::t('failSendEmail'), 'e_signup');
            Log::warn(BaseException::convertExceptionToString($e));
        }
    }

    public function login()
    {
        $users = $this->getUsers();
        $users->login_last = DateTime::set()->isoDatetime();
        if (!$users->save()) {
            $this->addErrorAsPlaceholder(i18n::t('failSignup'), 'e_signup');
            return;
        }

        $data = $users->toArray();
        $this->_user->addMulti(ArrayHelper::intersectByKeys($data, ['id', 'username', 'url']));
        $this->_user->login();
    }

    public function beforeSignup()
    {
        $event = new ModelEvent();
        $this->trigger(self::EVENT_BEFORE_SIGNUP, $event);
        return $event->isValid;
    }

    public function afterSignup()
    {
        if (!$users = BaseUsers::create($this->getAttributes(), $this->defaultStatus, $this->generateToken)) {
            $this->addErrorAsPlaceholder(i18n::t('failSignup'), 'e_signup');
            return false;
        }
        $this->users = $users;
        $this->users->id = $this->users->primaryKey;
        $this->isSignup = true;
        $result = $users->toArray();

        $event = new ModelEvent();
        $event->result = $result;
        $this->trigger(self::EVENT_AFTER_SIGNUP, $event);

        return true;
    }

    protected function validateExistsUser()
    {
        if ($this->hasErrors()) {
            return true;
        }
        if (BaseUsers::existsByUsernameOrEmail($this->email, $this->username, null)) {
            $this->addErrorAsPlaceholder(i18n::t('existsUsernameOrEmail'), 'e_signup');
            return false;
        }
        return true;
    }

    protected function validateCSRF($input)
    {
        $v = Validate::required()->csrf()->placeholders(['name' => 'CSRF-token']);
        if (!$v->validate($input)) {
            $this->addErrorAsPlaceholder($v->getFirstError(), 'e_signup');
            return false;
        }
        return true;
    }
} 