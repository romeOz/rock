<?php

namespace rock\validate\rules;


use rock\base\ObjectTrait;

abstract class Rule
{
    use ObjectTrait{
        ObjectTrait::__construct as parentConstruct;
    }

    public $params = [];

    /**
     * @param mixed $input
     * @return bool
     */
    abstract public function validate($input);
}