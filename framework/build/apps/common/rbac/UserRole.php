<?php

namespace apps\common\rbac;


use rock\rbac\Role;
use rock\Rock;

class UserRole extends Role
{
    public function execute(array $params = null)
    {
        return !Rock::$app->user->isGuest();
    }
}