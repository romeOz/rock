<?php

namespace rock\user;


use rock\events\Event;

class UserEvent extends Event
{
    /**
     * @var boolean whether the login or logout should proceed.
     * Event handlers may modify this property to determine whether the login or logout should proceed.
     * This property is only meaningful for {@see \rock\user\User::EVENT_BEFORE_LOGIN} and {@see \rock\user\User::EVENT_BEFORE_LOGOUT} events.
     */
    public $isValid = true;
}