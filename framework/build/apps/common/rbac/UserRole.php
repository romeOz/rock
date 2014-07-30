<?php

namespace apps\common\rbac;


use rock\rbac\Role;

class UserRole extends Role
{
    public function execute(array $params = null)
    {
        return !$this->Rock->user->isGuest();
    }
}