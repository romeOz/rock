<?php
namespace rock\validation\exceptions\ru;

use rock\validation\exceptions\ValidationException;

class FileSizeMaxException extends ValidationException
{
    const INCLUSIVE = 1;

    public static $defaultTemplates = array(
        self::MODE_DEFAULT => array(
            self::STANDARD => 'Размер {{name}} должен быть меньше {{maxValue}} байт',
            self::INCLUSIVE => 'Размер {{name}} должен быть меньше или равен {{maxValue}} байт',
        ),
        self::MODE_NEGATIVE => array(
            self::STANDARD => 'Размер {{name}} не должен быть меньше {{maxValue}} байт',
            self::INCLUSIVE => 'Размер {{name}} не дожен быть меньше или равен {{maxValue}} байт',
        )
    );

    public function chooseTemplate()
    {
        return $this->getParam('inclusive') ? static::INCLUSIVE : static::STANDARD;
    }
}

