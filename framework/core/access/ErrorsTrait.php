<?php

namespace rock\access;


trait ErrorsTrait 
{
    public function isErrorVerbs()
    {
        return (bool)(self::E_VERBS & $this->errors);
    }

    public function isErrorUsers()
    {
        return (bool)(self::E_USERS & $this->errors);
    }
    public function isErrorRoles()
    {
        return (bool)(self::E_ROLES & $this->errors);
    }
    public function isErrorIps()
    {
        return (bool)(self::E_IPS & $this->errors);
    }

    public function isErrorCustom()
    {
        return (bool)(self::E_CUSTOM & $this->errors);
    }

    public function isErrorNotFound()
    {
        return (bool)(self::E_NOT_FOUND & $this->errors);
    }

    public function isErrors()
    {
        return (bool)$this->errors;
    }

    /**
     * @return int
     */
    public function getErrors()
    {
        return $this->errors;
    }

} 