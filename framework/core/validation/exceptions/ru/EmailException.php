<?php
namespace rock\validation\exceptions\ru;

use rock\validation\exceptions\ValidationException;

class EmailException extends ValidationException
{
    public static $defaultTemplates = array(
        self::MODE_DEFAULT => array(
            self::STANDARD => '{{name}} email должен быть верным',
        ),
        self::MODE_NEGATIVE => array(
            self::STANDARD => '{{name}} email не должен быть верным',
        )
    );
}

