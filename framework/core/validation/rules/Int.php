<?php
namespace rock\validation\rules;

class Int extends AbstractRule
{
    public function validate($input)
    {
        return is_numeric($input) && (int) $input == $input;
    }
}

