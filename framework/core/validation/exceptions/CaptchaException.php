<?php

namespace rock\validation\exceptions;


class CaptchaException extends ValidationException
{
    public static $defaultTemplates = array(
        self::MODE_DEFAULT => array(
            self::STANDARD => 'captcha must be valid',
        ),
        self::MODE_NEGATIVE => array(
            self::STANDARD => 'captcha must not be valid',
        )
    );
} 