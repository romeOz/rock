<?php
namespace rock\validation\rules;

class Json extends AbstractRule
{
    public function validate($input)
    {
        return (bool) (json_decode($input));
    }
}

