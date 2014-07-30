<?php

namespace rock\validation\exceptions\ru;


class LengthException extends \Respect\Validation\Exceptions\LengthException
{
    public static $defaultTemplates = array(
        self::MODE_DEFAULT => array(
            self::BOTH => '{{name}} должно иметь длину в диапазоне от {{minValue}} до {{maxValue}}',
            self::LOWER => '{{name}} должно иметь длину больше {{minValue}}',
            self::GREATER => '{{name}} должно иметь длину меньше {{maxValue}}',
        ),
        self::MODE_NEGATIVE => array(
            self::BOTH => '{{name}} не должно иметь длину в диапазоне от {{minValue}} до {{maxValue}}',
            self::LOWER => '{{name}} не должно иметь длину больше {{minValue}}',
            self::GREATER => '{{name}} не должно иметь длину меньше {{maxValue}}',
        )
    );
} 