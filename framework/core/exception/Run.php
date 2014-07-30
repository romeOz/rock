<?php

namespace rock\exception;


class Run extends \Whoops\Run
{
    public function setSendHttpCode($code = 500)
    {
        $this->sendHttpCode = $code;
    }
} 