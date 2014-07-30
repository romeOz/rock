<?php
namespace rock\validation\exceptions;

class ZendException extends AbstractNestedException
{
    public static $defaultTemplates = array(
        self::MODE_DEFAULT => array(
            self::STANDARD => '{{name}}',
        ),
        self::MODE_NEGATIVE => array(
            self::STANDARD => '{{name}}',
        )
    );
}

