<?php
namespace rock\validation\rules;

class NullValue extends NotEmpty
{
    public function validate($input)
    {
        return is_null($input);
    }
}

