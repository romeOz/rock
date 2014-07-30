<?php
namespace rock\validation\exceptions;

class InstanceException extends ValidationException
{
    public static $defaultTemplates = array(
        self::MODE_DEFAULT => array(
            self::STANDARD => '{{name}} must be an instance of {{instanceName}}',
        ),
        self::MODE_NEGATIVE => array(
            self::STANDARD => '{{name}} must not be an instance of {{instanceName}}',
        )
    );
}

