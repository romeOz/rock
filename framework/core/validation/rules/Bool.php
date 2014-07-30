<?php
namespace rock\validation\rules;

class Bool extends AbstractRule
{
    public function validate($input)
    {
        return is_bool($input);
    }
}

