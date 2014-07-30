<?php

namespace rock\validation\exceptions;

class UploadedException extends ValidationException
{

    public static $defaultTemplates = array(
        self::MODE_DEFAULT => array(
            self::STANDARD => '{{name}} must be an uploaded file',
        ),
        self::MODE_NEGATIVE => array(
            self::STANDARD => '{{name}} must not be an uploaded file',
        )
    );

}
