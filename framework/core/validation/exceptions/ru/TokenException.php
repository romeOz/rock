<?php

namespace rock\validation\exceptions\ru;


use rock\validation\exceptions\ValidationException;

class TokenException extends ValidationException
{
    public static $defaultTemplates = array(
        self::MODE_DEFAULT => array(
            self::STANDARD => 'csrf-токен должен быть верным',
        ),
        self::MODE_NEGATIVE => array(
            self::STANDARD => 'csrf-токен не должен быть верным',
        )
    );
} 