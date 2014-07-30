<?php
namespace rock\validation\rules;

class Negative extends AbstractRule
{
    public function validate($input)
    {
        return $input < 0;
    }
}

