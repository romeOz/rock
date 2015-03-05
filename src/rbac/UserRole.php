<?php

namespace rock\rbac;

use rock\Rock;

class UserRole extends Role
{
    public function execute(array $params = null)
    {
        return !Rock::$app->user->isGuest();
    }
}