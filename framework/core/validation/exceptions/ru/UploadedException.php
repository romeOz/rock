<?php

namespace rock\validation\exceptions\ru;

use rock\validation\exceptions\ValidationException;

class UploadedException extends ValidationException
{

    public static $defaultTemplates = array(
        self::MODE_DEFAULT => array(
            self::STANDARD => '{{name}} должен быть загружен',
        ),
        self::MODE_NEGATIVE => array(
            self::STANDARD => '{{name}} не должен быть загружен',
        )
    );

}
