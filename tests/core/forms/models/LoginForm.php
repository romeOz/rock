<?php

namespace rockunit\core\forms\models;


use apps\common\models\forms\BaseLoginForm;
use rock\Rock;
use rockunit\core\db\models\BaseUsers;

class LoginForm extends BaseLoginForm
{
    /**
     * Finds user by `email`
     *
     * @return BaseUsers
     */
    public function getUsers()
    {
        if (!isset($this->_users)) {
            if (!$this->_users = BaseUsers::findOneByEmail($this->email, BaseUsers::STATUS_ACTIVE, false)) {
                $this->Rock->template->addPlaceholder('e_login', Rock::t('notExistsUser'), true);
            }
        }

        return $this->_users;
    }

}