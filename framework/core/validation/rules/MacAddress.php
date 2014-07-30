<?php
namespace rock\validation\rules;

class MacAddress extends AbstractRule
{
    public function validate($input)
    {
        return !empty($input) && preg_match('/^(([0-9a-fA-F]{2}-){5}|([0-9a-fA-F]{2}:){5})[0-9a-fA-F]{2}$/', $input);
    }
}

