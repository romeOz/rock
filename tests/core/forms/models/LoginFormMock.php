<?php

namespace rockunit\core\forms\models;

use apps\common\models\forms\BaseLoginForm;
use rock\i18n\i18n;
use rockunit\core\db\models\BaseUsers;

class LoginFormMock extends BaseLoginForm
{
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
}