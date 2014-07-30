<?php

namespace apps\common\models\forms;


use apps\common\models\users\BaseUsers;
use rock\base\Config;
use rock\base\Model;
use rock\event\Event;
use rock\helpers\ArrayHelper;
use rock\helpers\Helper;
use rock\helpers\Sanitize;
use rock\helpers\String;
use rock\mail\Exception;
use rock\Rock;
use rock\validation\Validation;

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
    public $csrf;

    public $emailBodyTpl = '@common.views/email/{lang}/recovery';
    public $redirectUrl;

    public $isRecovery = false;

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
                            ->setPlaceholders('e_recovery')
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
                                'captcha',
                                Validation::notEmpty()
                                    ->captcha($this->Rock->captcha->getSession(), true)
                                    ->setName(Rock::t('captcha'))
                            )
                            ->setModel($this)
                            ->setPlaceholders(
                                [
                                    'email.first',
                                    'captcha.first'
                                ]
                            )
                            ->validate($attributes) === false) {
                        return false;
                    }

                    return $this->validateEmail();

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
        return ['email', 'captcha', $this->Rock->token->csrfPrefix];
    }

    public function attributeLabels()
    {
        return [
            'email' => Rock::t('email'),
            'captcha'=> Rock::t('captcha'),
        ];
    }

    /**
     * Validates the email.
     * This method serves as the inline validation for password.
     */
    public function validateEmail()
    {
        if (!$this->getUsers()) {
            $this->Rock->template->addPlaceholder('e_recovery', Rock::t('invalidEmail'), true);
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
            if (!$this->_users = BaseUsers::find()->byStatus(BaseUsers::STATUS_ACTIVE)->byEmail($this->email)->one()) {
                $this->Rock->template->addPlaceholder('e_recovery', Rock::t('invalidEmail'), true);
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
        return $this->Rock->template->getChunk($chunk, $data);
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
            $this->Rock->mail
                ->address($this->email)
                ->subject($subject)
                ->body($body)
                ->send();
        } catch (\Exception $e) {
            $this->Rock->template->addPlaceholder('e_recovery', Rock::t('failSendEmail'), true);
            new Exception(Exception::ERROR, $e->getMessage(), null, $e);
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
        $this->Rock->session->setFlash('successRecovery', ['email' => String::replaceRandChars($this->email)]);

        $response = $this->Rock->response;
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
        if ($this->trigger(self::EVENT_BEFORE_RECOVERY)->before() === false) {
            return false;
        }

        return true;
    }

    public function afterRecovery()
    {
        $users = $this->getUsers();
        $this->password = Rock::$app->security->generateRandomKey(7);
        $users->setPassword($this->password);
        if (!$users->save()) {
            $this->Rock->template->addPlaceholder('e_recovery', Rock::t('failRecovery'), true);
            return false;
        }
        $this->isRecovery = true;
        $result = $users->toArray();
        if ($this->trigger(self::EVENT_AFTER_RECOVERY, Event::AFTER)->after(null, $result) === false) {
            return false;
        }
        return true;
    }
} 