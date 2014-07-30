<?php
namespace rock\validation\exceptions\ru;

use rock\validation\exceptions\ValidationException;

class NumericException extends ValidationException
{
    public static $defaultTemplates = array(
        self::MODE_DEFAULT => array(
            self::STANDARD => '{{name}} должно быть числом',
        ),
        self::MODE_NEGATIVE => array(
            self::STANDARD => '{{name}} не должно быть числом',
        )
    );
}

