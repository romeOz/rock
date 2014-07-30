<?php
namespace rock\validation\exceptions;

class FileSizeMaxException extends ValidationException
{
    const INCLUSIVE = 1;

    public static $defaultTemplates = array(
        self::MODE_DEFAULT => array(
            self::STANDARD => 'Size of {{name}} must be lower than {{maxValue}} bytes',
            self::INCLUSIVE => 'Size of {{name}} must be lower than or equals {{maxValue}} bytes',
        ),
        self::MODE_NEGATIVE => array(
            self::STANDARD => 'Size of {{name}} must not be lower than {{maxValue}} bytes',
            self::INCLUSIVE => 'Size of {{name}} must not be lower than or equals {{maxValue}} bytes',
        )
    );

    public function chooseTemplate()
    {
        return $this->getParam('inclusive') ? static::INCLUSIVE : static::STANDARD;
    }
}

