<?php

namespace rock\validation\exceptions\ru;


use rock\validation\exceptions\ValidationException;

class NotEmptyException extends ValidationException
{
    const STANDARD = 0;
    const NAMED = 1;
    public static $defaultTemplates = array(
        self::MODE_DEFAULT => array(
            self::STANDARD => 'значение не должно быть пустым',
            self::NAMED => '{{name}} не должно быть пустым',
        ),
        self::MODE_NEGATIVE => array(
            self::STANDARD => 'значение должно быть пустым',
            self::NAMED => '{{name}} должно быть пустым',
        )
    );

    public function chooseTemplate()
    {
        return static::STANDARD;//$this->getName() == "" ? static::STANDARD : static::NAMED;
    }
} 