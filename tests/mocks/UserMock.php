<?php
namespace rockunit\mocks;

use rock\base\ComponentsTrait;
use rock\base\ObjectTrait;
use rock\user\User;
use rockunit\core\db\models\Users;

class UserMock extends User
{
    public function activate($token, $autoLogin = false)
    {
        if (empty($token) || (!$users = Users::findByToken($token))) {
            return false;
        }

        $users->removeToken();
        $users->setStatus(Users::STATUS_ACTIVE);
        $users->save();

        if ($autoLogin === true) {
            $this->addMulti($users->toArray());
            $this->login();
        }

        return true;
    }
}