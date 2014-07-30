<?php
namespace rock\validation\exceptions;

class AlwaysInvalidException extends ValidationException
{
    public static $defaultTemplates = array(
        self::MODE_DEFAULT => array(
            self::STANDARD => '{{name}} is always invalid',
        ),
        self::MODE_NEGATIVE => array(
            self::STANDARD => '{{name}} is always valid',
        )
    );
}

