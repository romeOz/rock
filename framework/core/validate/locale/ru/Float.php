<?php

namespace rock\validate\locale\ru;


use rock\validate\locale\Locale;

/**
 * Class Float
 *
 * @codeCoverageIgnore
 * @package rock\validate\locale\ru
 */
class Float extends Locale
{
    public function defaultTemplates()
    {
        return [
            self::MODE_DEFAULT => [
                self::STANDARD => '{{name}} должно быть вещественным числом',
            ],
            self::MODE_NEGATIVE => array(
                self::STANDARD => '{{name}} не должно быть вещественным числом',
            )
        ];
    }

    public function defaultPlaceholders()
    {
        return ['name' => 'значение'];
    }
}