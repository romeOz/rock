<?php
namespace rock\validation\exceptions\ru;

use rock\validation\exceptions\ValidationException;

class FileSizeMinException extends ValidationException
{
    const INCLUSIVE = 1;

    public static $defaultTemplates = array(
        self::MODE_DEFAULT => array(
            self::STANDARD => 'Размер {{name}} должен быть больше {{minValue}} байт',
            self::INCLUSIVE => 'Размер {{name}} должен быть больше или равен {{minValue}} байт',
        ),
        self::MODE_NEGATIVE => array(
            self::STANDARD => 'Размер {{name}} не должен быть больше {{minValue}} байт',
            self::INCLUSIVE => 'Размер {{name}} не должен быть больше или равен {{minValue}} байт',
        )
    );

    public function chooseTemplate()
    {
        return $this->getParam('inclusive') ? static::INCLUSIVE : static::STANDARD;
    }
}

