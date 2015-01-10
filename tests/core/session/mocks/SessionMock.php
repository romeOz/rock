<?php

namespace rockunit\core\session\mocks;


use rock\session\Session;

class SessionMock extends Session
{
    public function init()
    {
    }

    public function open()
    {
        $this->updateFlashCounters();
    }
}