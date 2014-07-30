<?php
namespace rock\validation\rules;

class Digits extends Digit
{
    public function __construct()
    {
        parent::__construct();
        trigger_error("Use digit instead.",
            E_USER_DEPRECATED);
    }
}
