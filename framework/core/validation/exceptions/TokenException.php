<?php

namespace rock\validation\exceptions;


class TokenException extends ValidationException
{
    public static $defaultTemplates = array(
        self::MODE_DEFAULT => array(
            self::STANDARD => 'must be valid csrf-token',
        ),
        self::MODE_NEGATIVE => array(
            self::STANDARD => 'invalid csrf-token',
        )
    );
} 