<?php
namespace rock\validation\rules;

class Odd extends AbstractRule
{
    public function validate($input)
    {
        return ((int) $input % 2 !== 0);
    }
}

