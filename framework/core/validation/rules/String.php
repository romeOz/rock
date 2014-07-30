<?php
namespace rock\validation\rules;

class String extends AbstractRule
{
    public function validate($input)
    {
        return is_string($input);
    }
}

