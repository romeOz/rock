<?php
namespace rock\validation\exceptions;

class NoWhitespaceException extends ValidationException
{
    public static $defaultTemplates = array(
        self::MODE_DEFAULT => array(
            self::STANDARD => '{{name}} must not contain whitespace',
        ),
        self::MODE_NEGATIVE => array(
            self::STANDARD => '{{name}} must not not contain whitespace',
        )
    );
}

