
<?php

namespace rock\validation\exceptions;

class SymbolicLinkException extends ValidationException
{

    public static $defaultTemplates = array(
        self::MODE_DEFAULT => array(
            self::STANDARD => '{{name}} must be a symbolic link',
        ),
        self::MODE_NEGATIVE => array(
            self::STANDARD => '{{name}} must not be a symbolic link',
        )
    );

}
