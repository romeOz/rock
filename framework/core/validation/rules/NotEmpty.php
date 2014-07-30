<?php
namespace rock\validation\rules;

class NotEmpty extends AbstractRule
{
    public function validate($input)
    {
        if (is_string($input)) {
            $input = trim($input);
        }

        return !empty($input);
    }
}

