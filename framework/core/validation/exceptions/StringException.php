<?php
namespace rock\validation\exceptions;

class StringException extends ValidationException
{
    public static $defaultTemplates = array(
        self::MODE_DEFAULT => array(
            self::STANDARD => '{{name}} must be a string',
        ),
        self::MODE_NEGATIVE => array(
            self::STANDARD => '{{name}} must not be string',
        )
    );
}

