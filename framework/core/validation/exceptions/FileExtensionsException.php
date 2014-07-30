<?php

namespace rock\validation\exceptions;

class FileExtensionsException extends ValidationException
{
    public static $defaultTemplates = array(
        self::MODE_DEFAULT => array(
            self::STANDARD => 'Extension of {{name}} must be: {{extensions}}',
        ),
        self::MODE_NEGATIVE => array(
            self::STANDARD => 'Extension of {{name}} must not be: {{extensions}}',
        )
    );
}
