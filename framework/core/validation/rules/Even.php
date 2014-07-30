<?php
namespace rock\validation\rules;

class Even extends AbstractRule
{
    public function validate($input)
    {
        return ( (int) $input % 2 === 0);
    }
}

