<?php

namespace rock\validation\exceptions\ru;


use rock\validation\exceptions\ValidationException;

class ConfirmException extends ValidationException
{
    public static $defaultTemplates = array(
        self::MODE_DEFAULT => array(
            self::STANDARD => 'значения должны совпадать',
        ),
        self::MODE_NEGATIVE => array(
            self::STANDARD => 'значения не должны совпадать',
        )
    );

} 