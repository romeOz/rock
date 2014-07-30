<?php
namespace rock\validation\exceptions;

class FileSizeMinException extends ValidationException
{
    const INCLUSIVE = 1;

    public static $defaultTemplates = array(
        self::MODE_DEFAULT => array(
            self::STANDARD => '{{name}} size must be greater than {{minValue}} bytes',
            self::INCLUSIVE => '{{name}} size must be greater than or equals {{minValue}} bytes',
        ),
        self::MODE_NEGATIVE => array(
            self::STANDARD => '{{name}} size must not be greater than {{minValue}} bytes',
            self::INCLUSIVE => '{{name}} size must not be greater than or equals {{minValue}} bytes',
        )
    );

    public function chooseTemplate()
    {
        return $this->getParam('inclusive') ? static::INCLUSIVE : static::STANDARD;
    }
}

