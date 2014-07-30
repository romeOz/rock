<?php
namespace rock\validation\rules;

class Positive extends AbstractRule
{
    public function validate($input)
    {
        return $input > 0;
    }
}

