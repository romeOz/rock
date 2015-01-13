<?php

namespace rockunit\core\security\mocks;


use rock\security\Security;

class SecurityMock extends Security
{
    /**
     * @inheritdoc
     */
    public function hkdf($algo, $inputKey, $salt = null, $info = null, $length = 0)
    {
        return parent::hkdf($algo, $inputKey, $salt, $info, $length);
    }

    /**
     * @inheritdoc
     */
    public function pbkdf2($algo, $password, $salt, $iterations, $length = 0)
    {
        return parent::pbkdf2($algo, $password, $salt, $iterations, $length);
    }
}