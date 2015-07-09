<?php

namespace rockunit\mocks;


use rock\session\Session;

class SessionMock extends Session
{
    public static $isActive = false;
    public function init()
    {
    }

    public function open()
    {
        if (static::$isActive) {
            return;
        }
        $this->updateFlashCounters();
    }
}