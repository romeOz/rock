<?php

namespace rock\sanitize\rules;


use rock\base\ObjectTrait;

abstract class Rule
{
    use ObjectTrait{
        ObjectTrait::__construct as parentConstruct;
    }

    /**
     * @param mixed $input
     * @return bool
     */
    abstract public function sanitize($input);
} 