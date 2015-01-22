<?php

namespace apps\common\models\forms;


use apps\common\models\users\BaseUsers;
use rock\base\Model;
use rock\base\ModelEvent;
use rock\captcha\Captcha;
use rock\csrf\CSRF;
use rock\db\Session;
use rock\di\Container;
use rock\exception\BaseException;
use rock\helpers\ArrayHelper;
use rock\helpers\Helper;
use rock\helpers\StringHelper;
use rock\mail\Mail;
use rock\response\Response;
use rock\Rock;
use rock\template\Template;
use rock\validate\Validate;

class BaseRecoveryForm extends Model
{
    const EVENT_BEFORE_RECOVERY = 'beforeRecovery';
    const EVENT_AFTER_RECOVERY = 'afterRecovery';


    /** @var  string */
    public $email;
    /** @var  string */
    public $password;
    /** @var  string */
    public $captcha;
    /** @var  string */
    public $_csrf;

    public $emailBodyTpl = '@common.views/email/{lang}/recovery';
    public $redirectUrl;

    public $isRecovery = false;

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

    public function init()
    {
        parent::init();

        $this->_csrfInstance = Container::load('csrf');
        $this->_response = Container::load('response');
        $this->_captcha = Container::load('captcha');
        $this->_template = Container::load('template');
        $this->_mail = Container::load('mail');
        $this->_session = Container::load('session');
    }
    
    public function rules()
    {
        return [
            [
                self::RULE_VALIDATE, '_csrf', 'validateCSRF', 'one'
            ],
            [
                self::RULE_SANITIZE, ['email', 'captcha'], 'trim'
            ],
            [
                self::RULE_VALIDATE, ['email', 'captcha'], 'required',
            ],
            [
                self::RULE_VALIDATE, 'email', 'length' => [4, 80, true], 'email'
            ],
            [
                self::RULE_VALIDATE, 'captcha', 'captcha' => [$this->_captcha->getSession()]
            ],
            [
                self::RULE_SANITIZE, 'email', 'lowercase'
            ],
            [
                self::RULE_SANITIZE, ['email', 'captcha'], 'removeTags'
            ],
            [
                self::RULE_VALIDATE, 'email', 'validateEmail'
            ],
        ];
    }

    public function safeAttributes()
    {
        return ['email', 'captcha', $this->_csrfInstance->csrfParam];
    }

    public function attributeLabels()
    {
        return [
            'email' => Rock::t('email'),
            'captcha'=> Rock::t('captcha'),
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
            if (!$this->_users = BaseUsers::findOneByEmail($this->email, BaseUsers::STATUS_ACTIVE, false)) {
                $this->addErrorAsPlaceholder(Rock::t('invalidEmail'), 'e_recovery');
            }
        }

        return $this->_users;
    }

    public function validate(array $attributes = NULL, $clearErrors = true)
    {
        if (!$this->beforeRecovery() || !parent::validate()) {
            return false;
        }

        return $this->afterRecovery();
    }

    protected function prepareBody($chunk)
    {
        $data = $this->getUsers()->toArray();
        $name = $data['username'];

        if (isset($data['firstname']) || isset($data['lastname'])) {
            $name = implode(' ', ArrayHelper::intersectByKeys($data, ['firstname', 'lastname']));
        }

        $data['fullname'] = $name;
        $data['password'] = $this->password;
        $data['email'] = $this->email;
        return $this->_template->getChunk($chunk, $data);
    }

    public function sendMail($subject = null, $chunkBody = null)
    {
        if ($this->isRecovery === false) {
            return $this;
        }

        if (!isset($subject)) {
            $subject = Rock::t('subjectRecovery', ['site_name' => Rock::t('siteName')]);
        }

        $body = $this->prepareBody(Helper::getValue($chunkBody, $this->emailBodyTpl));

        try {
            $this->_mail
                ->address($this->email)
                ->subject($subject)
                ->body($body)
                ->send();
        } catch (\Exception $e) {
            $this->addErrorAsPlaceholder(Rock::t('failSendEmail'), 'e_recovery');
            Rock::warning(BaseException::convertExceptionToString($e));
        }

        return $this;
    }

    /**
     * @param string|null $url
     */
    public function redirect($url = null)
    {
        if ($this->isRecovery === false) {
            return;
        }
        $this->_session->setFlash('successRecovery', ['email' => StringHelper::replaceRandChars($this->email)]);

        $response = $this->_response;
        if (!isset($url) && isset($this->redirectUrl)) {
            $url = $this->redirectUrl;
        }
        if (!isset($url)) {
            $response->refresh()->send(true);
        }

        $response->redirect($url)->send(true);
    }

    public function beforeRecovery()
    {
        $event = new ModelEvent();
        $this->trigger(self::EVENT_BEFORE_RECOVERY, $event);
        return $event->isValid;
    }

    public function afterRecovery()
    {
        $users = $this->getUsers();
        $this->password = Rock::$app->security->generateRandomKey(7);
        $users->setPassword($this->password);
        if (!$users->save()) {
            $this->addErrorAsPlaceholder(Rock::t('failRecovery'), 'e_recovery');
            return false;
        }
        $this->isRecovery = true;
        $result = $users->toArray();
        $event = new ModelEvent();
        $event->result = $result;
        $this->trigger(self::EVENT_AFTER_RECOVERY, $event);
        return true;
    }

    protected function validateCSRF($input)
    {
        $v = Validate::required()->csrf()->placeholders(['name' => 'CSRF-token']);
        if (!$v->validate($input)) {
            $this->addErrorAsPlaceholder($v->getFirstError(), 'e_recovery');
            return false;
        }

        return true;
    }

    /**
     * Validates the email.
     * This method serves as the inline validation for password.
     */
    protected function validateEmail()
    {
        if ($this->hasErrors()) {
            return true;
        }
        if (!$this->getUsers()) {
            return false;
        }

        return true;
    }
} 