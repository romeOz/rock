<?php

namespace rockunit\core\forms\models;


use apps\common\models\forms\BaseRecoveryForm;
use rock\i18n\i18n;
use rockunit\core\db\models\BaseUsers;

class RecoveryFormMock extends BaseRecoveryForm
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
                $this->addErrorAsPlaceholder(i18n::t('invalidEmail'), 'e_recovery');
            }
        }

        return $this->_users;
    }
} 