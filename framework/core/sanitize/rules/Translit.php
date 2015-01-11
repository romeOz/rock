<?php

namespace rock\sanitize\rules;

class Translit extends Rule
{
    /**
     * @inheritdoc
     */
    public function sanitize($input)
    {
        return is_string($input) ? \rock\helpers\StringHelper::translit($input) : $input;
    }
} 