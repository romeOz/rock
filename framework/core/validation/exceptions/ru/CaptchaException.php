<?php

namespace rock\validation\exceptions\ru;


use rock\validation\exceptions\ValidationException;

class CaptchaException extends ValidationException
{
    public static $defaultTemplates = array(
        self::MODE_DEFAULT => array(
            self::STANDARD => 'каптча должна быть верной',
        ),
        self::MODE_NEGATIVE => array(
            self::STANDARD => 'каптча не должна быть верной',
        )
    );
} 