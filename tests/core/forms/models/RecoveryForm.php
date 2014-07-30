<?php

namespace rockunit\core\forms\models;


use apps\common\models\forms\BaseRecoveryForm;
use rock\Rock;
use rockunit\core\db\models\BaseUsers;

class RecoveryForm extends BaseRecoveryForm
{
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
} 