<?php
namespace rock\validation\exceptions;

class ObjectException extends ValidationException
{
    public static $defaultTemplates = array(
        self::MODE_DEFAULT => array(
            self::STANDARD => '{{name}} must be an object',
        ),
        self::MODE_NEGATIVE => array(
            self::STANDARD => '{{name}} must not be an object',
        )
    );
}

