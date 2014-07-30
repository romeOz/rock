<?php
namespace rock\validation\exceptions;

class NotEmptyException extends ValidationException
{
    const STANDARD = 0;
    const NAMED = 1;
    public static $defaultTemplates = array(
        self::MODE_DEFAULT => array(
            self::STANDARD => 'the value must not be empty',
            self::NAMED => '{{name}} must not be empty',
        ),
        self::MODE_NEGATIVE => array(
            self::STANDARD => 'the value must be empty',
            self::NAMED => '{{name}} must be empty',
        )
    );

    public function chooseTemplate()
    {
        return static::STANDARD;//$this->getName() == "" ? static::STANDARD : static::NAMED;
    }
}

