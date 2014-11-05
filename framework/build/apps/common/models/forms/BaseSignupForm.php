<?php

namespace apps\common\models\forms;

use apps\common\models\users\BaseUsers;
use rock\base\Model;
use rock\base\ModelEvent;
use rock\db\Connection;
use rock\event\Event;
use rock\exception\ErrorHandler;
use rock\helpers\ArrayHelper;
use rock\helpers\Helper;
use rock\helpers\String;
use rock\Rock;
use rock\url\Url;
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
                self::RULE_VALIDATE, 'captcha', 'captcha' => [$this->Rock->captcha->getSession()]
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
        return ['email', 'username', 'password', 'password_confirm', 'captcha', $this->Rock->csrf->csrfParam];
    }

    public function attributeLabels()
    {
        return [
            'email'=> Rock::t('email'),
            'password'=> Rock::t('password'),
            'username'=> Rock::t('username'),
            'password_confirm'=> Rock::t('confirmPassword'),
            'captcha'=> Rock::t('captcha'),
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

        $this->Rock->session->setFlash('successSignup', ['email' => String::replaceRandChars($this->email)]);

        $response = $this->Rock->response;
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
            /** @var Url $urlBuilder */
            $urlBuilder = Rock::factory($this->activateUrl, Url::className());
            $data['url'] = $urlBuilder
                ->addArgs(['token' => $data['token']])
                ->getAbsoluteUrl(true);
        }

        return $this->Rock->template->getChunk($chunk, $data);
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
            $subject = Rock::t('subjectRegistration', ['site_name' => Rock::t('siteName')]);
        }

        $body = $this->prepareBody($this->getUsers()->toArray(['username', 'email','token']), Helper::getValue($chunkBody, $this->emailBodyTpl));

        try {
            $this->Rock->mail
                ->address($this->email)
                ->subject($subject)
                ->body($body)
                ->send();
        } catch (\Exception $e) {
            $this->addErrorAsPlaceholder(Rock::t('failSendEmail'), 'e_signup');
            Rock::warning(ErrorHandler::convertExceptionToString($e));
        }
    }

    public function login()
    {
        $users = $this->getUsers();
        $users->login_last = $this->Rock->date->isoDatetime();
        if (!$users->save()) {
            $this->addErrorAsPlaceholder(Rock::t('failSignup'), 'e_signup');
            return;
        }

        $data = $users->toArray();
        $user = $this->Rock->user;
        $user->addMulti(ArrayHelper::intersectByKeys($data, ['id', 'username', 'url']));
        $user->login();
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
            $this->addErrorAsPlaceholder(Rock::t('failSignup'), 'e_signup');
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
            $this->addErrorAsPlaceholder(Rock::t('existsUsernameOrEmail'), 'e_signup');
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