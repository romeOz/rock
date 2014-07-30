<?php
namespace rock\validation\rules;

class AlwaysInvalid extends AbstractRule
{
    public function validate($input)
    {
        return false;
    }
}

