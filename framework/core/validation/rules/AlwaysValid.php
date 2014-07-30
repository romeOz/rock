<?php
namespace rock\validation\rules;

class AlwaysValid extends AbstractRule
{
    public function validate($input)
    {
        return true;
    }
}

