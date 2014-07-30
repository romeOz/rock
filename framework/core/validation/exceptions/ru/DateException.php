<?php

namespace rock\validation\exceptions\ru;


class DateException extends \Respect\Validation\Exceptions\DateException
{
    public static $defaultTemplates = array(
        self::MODE_DEFAULT => array(
            self::STANDARD => '{{name}} должно соответствовать формату даты',
            self::FORMAT => '{{name}} должно соответствовать формату даты. Пример: {{format}}'
        ),
        self::MODE_NEGATIVE => array(
            self::STANDARD => '{{name}} не должно соответствовать формату даты',
            self::FORMAT => '{{name}} не должно соответствовать формату даты {{format}}'
        )
    );
} 