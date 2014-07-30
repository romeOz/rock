<?php
namespace rock\validation\rules;

class Object extends AbstractRule
{
    public function validate($input)
    {
        return is_object($input);
    }
}

