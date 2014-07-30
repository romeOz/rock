<?php

namespace rock\validation\exceptions\ru;


use rock\validation\exceptions\ValidationException;

class TokenException extends ValidationException
{
    public static $defaultTemplates = array(
        self::MODE_DEFAULT => array(
            self::STANDARD => 'токен должен быть верным',
        ),
        self::MODE_NEGATIVE => array(
            self::STANDARD => 'токен не должен быть верным',
        )
    );
} 