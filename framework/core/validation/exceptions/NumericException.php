<?php
namespace rock\validation\exceptions;

class NumericException extends ValidationException
{
    public static $defaultTemplates = array(
        self::MODE_DEFAULT => array(
            self::STANDARD => '{{name}} must be numeric',
        ),
        self::MODE_NEGATIVE => array(
            self::STANDARD => '{{name}} must not be numeric',
        )
    );
}

