<?php
namespace rock\validation\exceptions;

class VersionException extends ValidationException
{
    public static $defaultTemplates = array(
        self::MODE_DEFAULT => array(
            self::STANDARD => '{{name}} must be a version',
        ),
        self::MODE_NEGATIVE => array(
            self::STANDARD => '{{name}} must not be a version',
        )
    );
}

