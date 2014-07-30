<?php

namespace rock\validation\exceptions\ru;


use rock\validation\exceptions\ValidationException;

class MaxException extends ValidationException
{
    const INCLUSIVE = 1;

    public static $defaultTemplates = array(
        self::MODE_DEFAULT => array(
            self::STANDARD => '{{name}} должно быть меньше {{maxValue}}',
            self::INCLUSIVE => '{{name}} должно быть меньше или равно {{maxValue}}',
        ),
        self::MODE_NEGATIVE => array(
            self::STANDARD => '{{name}} не должно быть меньше {{maxValue}}',
            self::INCLUSIVE => '{{name}} не дожно быть меньше или равно {{maxValue}}',
        )
    );

    public function chooseTemplate()
    {
        return $this->getParam('inclusive') ? static::INCLUSIVE : static::STANDARD;
    }
} 