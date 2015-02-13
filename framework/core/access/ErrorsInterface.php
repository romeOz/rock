<?php

namespace rock\access;


interface ErrorsInterface 
{
    const E_VERBS = 1;
    const E_IPS = 2;
    const E_USERS = 4;
    const E_ROLES = 8;
    const E_CUSTOM = 16;
    const E_NOT_FOUND = 32;

    public function isErrorVerbs();
    public function isErrorUsers();
    public function isErrorRoles();
    public function isErrorIps();
    public function isErrorCustom();
    public function isErrorNotFound();
    public function getErrors();
} 