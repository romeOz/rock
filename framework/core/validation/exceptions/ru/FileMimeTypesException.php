<?php

namespace rock\validation\exceptions\ru;

use rock\validation\exceptions\ValidationException;

class FileMimeTypesException extends ValidationException
{

    public static $defaultTemplates = array(
        self::MODE_DEFAULT => array(
            self::STANDARD => 'Mime-type {{name}} должен быть: {{mimeTypes}}',
        ),
        self::MODE_NEGATIVE => array(
            self::STANDARD => 'Mime-type {{name}} не должен быть: {{mimeTypes}}',
        )
    );
}
