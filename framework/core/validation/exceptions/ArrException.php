<?php
namespace rock\validation\exceptions;

class ArrException extends ValidationException
{
    public static $defaultTemplates = array(
        self::MODE_DEFAULT => array(
            self::STANDARD => '{{name}} must be an array',
        ),
        self::MODE_NEGATIVE => array(
            self::STANDARD => '{{name}} must not be an array',
        )
    );
}

