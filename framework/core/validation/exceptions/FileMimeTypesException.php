<?php

namespace rock\validation\exceptions;

class FileMimeTypesException extends ValidationException
{
    public static $defaultTemplates = array(
        self::MODE_DEFAULT => array(
            self::STANDARD => 'Mime-type of {{name}} must be: {{mimeTypes}}',
        ),
        self::MODE_NEGATIVE => array(
            self::STANDARD => 'Mime-type of {{name}} must not be: {{mimeTypes}}',
        )
    );
}
