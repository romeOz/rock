<?php

namespace apps\common\models\forms;


use apps\common\models\users\BaseUsers;
use rock\base\Model;
use rock\db\Connection;
use rock\event\Event;
use rock\exception\ErrorHandler;
use rock\helpers\ArrayHelper;
use rock\helpers\Helper;
use rock\helpers\Sanitize;
use rock\helpers\String;
use rock\Rock;
use rock\url\Url;
use rock\validation\Validation;

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
    public $enableCaptcha = true;
    public $enableCsrfToken = true;

    public $isSignup = false;

    /** @var Connection */
    protected $connection;

    public function rules()
    {
        //$timestamp = time();
        return [
            [
                self::RULE_VALIDATION,
                function(array $attributes){

                    if ($this->enableCsrfToken) {
                        if ($this->Rock->validation
                                ->notEmpty()
                                ->token($this->formName())
                                ->setName(Rock::t('token'))
                                ->setModel($this)
                                ->setPlaceholders('e_signup')
                                ->validate($attributes[$this->Rock->token->csrfParam]) === false
                        ) {
                            return false;
                        }
                    }

                    $validation = $this->Rock->validation
                        ->key(
                            'email',
                            Validation::notEmpty()
                                ->length(4, 80, true)
                                ->email()
                        )
                        ->key(
                            'username',
                            Validation::notEmpty()
                                ->length(3, 80, true)
                                ->regex('/^[\w\s\-\*\@\%\#\!\?\.\)\(\+\=\~\:]+$/iu')
                        )
                        ->key(
                            'password',
                            Validation::notEmpty()
                                ->length(6, 20, true)
                                ->regex('/^[\\w\\d\-\.]+$/i')
                                ->setName(Rock::t('password'))
                        )
                        ->key(
                            'password_confirm',
                            Validation::notEmpty()
                                ->confirm(Helper::getValue($attributes['password']), true)
                        );

                    if ($this->enableCaptcha) {
                        $validation->key(
                            'captcha',
                            Validation::notEmpty()
                                ->captcha($this->Rock->captcha->getSession(), true)
                        );
                    }

                    if ($validation
                            ->setModel($this)
                            ->setPlaceholders(
                                [
                                    'email.first',
                                    'username.first' => 'e_username',
                                    'password.first' => 'e_password',
                                    'password_confirm.first' => 'e_password_confirm',
                                    'captcha.first' => 'e_captcha'
                                ]
                            )
                            ->validate($attributes) === false) {
                        return false;
                    }

                    if (BaseUsers::existsByUsernameOrEmail($attributes['email'], $attributes['username'], null) === true) {
                        $this->Rock->template->addPlaceholder('e_signup', Rock::t('existsUsernameOrEmail'), true);
                        return false;
                    }
                    return true;

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
        return ['email', 'username', 'password', 'password_confirm', 'captcha', $this->Rock->token->csrfParam];
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
            $this->Rock->template->addPlaceholder('e_signup', Rock::t('failSendEmail'), true);
            Rock::warning(ErrorHandler::convertExceptionToString($e));
        }
    }

    public function login()
    {
        $users = $this->getUsers();
        $users->login_last = $this->Rock->date->isoDatetime();
        if (!$users->save()) {
            $this->Rock->template->addPlaceholder('e_signup', Rock::t('failLogin'), true);
            return;
        }

        $data = $users->toArray();
        $user = $this->Rock->user;
        $user->addMulti(ArrayHelper::intersectByKeys($data, ['id', 'username', 'url']));
        $user->login();
    }

    public function beforeSignup()
    {
        if ($this->trigger(self::EVENT_BEFORE_SIGNUP)->before() === false) {
            //Event::offMulti([self::EVENT_AFTER_SIGNUP, self::EVENT_BEFORE_SIGNUP]);
            return false;
        }

        return true;
    }

    public function afterSignup()
    {
        if (!$users = BaseUsers::create($this->getAttributes(), $this->defaultStatus, $this->generateToken)) {
            $this->Rock->template->addPlaceholder('e_signup', Rock::t('failSignup'), true);
            return false;
        }
        $this->users = $users;
        $this->users->id = $this->users->primaryKey;
        $this->isSignup = true;
        $result = $users->toArray();
        if ($this->trigger(self::EVENT_AFTER_SIGNUP, Event::AFTER)->after(null, $result) === false) {
            return false;
        }


        return true;
    }
} 