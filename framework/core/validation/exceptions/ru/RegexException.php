<?php

namespace rock\validation\exceptions\ru;


use rock\validation\exceptions\ValidationException;

class RegexException extends ValidationException
{
    public static $defaultTemplates = array(
        self::MODE_DEFAULT => array(
            self::STANDARD => '{{name}} содержит неверные символы',
        ),
        self::MODE_NEGATIVE => array(
            self::STANDARD => '{{name}} не содержит верные символы',
        )
    );
} 