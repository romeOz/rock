<?php

namespace rock\validation\rules;


use rock\Rock;

class Token extends AbstractRule
{
    public $nameToken;
    public function __construct($name)
    {
        $this->nameToken = $name;
    }

    public function validate($input)
    {
        return Rock::$app->csrf->valid($input, $this->nameToken);
    }
} 