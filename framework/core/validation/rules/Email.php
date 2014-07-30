<?php
namespace rock\validation\rules;

class Email extends AbstractRule
{
    public function validate($input)
    {
        return is_string($input) && preg_match(
            '/^(\\w+[\\w\.\+\-]+)?\\w+@(\\w+\.)+\\w+$/iu',
            $input
        );
    }
}

