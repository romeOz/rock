<?php

namespace rock\validate\rules;


use rock\Rock;

class CSRF extends Rule
{
    /**
     * @inheritdoc
     */
    public function validate($input)
    {
        return Rock::$app->csrf->valid($input);
    }
} 