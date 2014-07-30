<?php

namespace rock\validation\exceptions\ru;

use rock\validation\exceptions\ValidationException;

class FileExtensionsException extends ValidationException
{

    public static $defaultTemplates = array(
        self::MODE_DEFAULT => array(
            self::STANDARD => 'Расширение {{name}} должно быть: {{extensions}}',
        ),
        self::MODE_NEGATIVE => array(
            self::STANDARD => 'Расширение {{name}} не должно быть: {{extensions}}',
        )
    );
}
