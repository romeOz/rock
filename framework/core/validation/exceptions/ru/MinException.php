<?php

namespace rock\validation\exceptions\ru;


class MinException extends \rock\validation\exceptions\MinException
{
    public static $defaultTemplates = array(
        self::MODE_DEFAULT => array(
            self::STANDARD => '{{name}} должно быть больше {{minValue}}',
            self::INCLUSIVE => '{{name}} должно быть больше или равно{{minValue}}',
        ),
        self::MODE_NEGATIVE => array(
            self::STANDARD => '{{name}} не должно быть больше {{minValue}}',
            self::INCLUSIVE => '{{name}} не должно быть больше или равно{{minValue}}',
        )
    );
} 