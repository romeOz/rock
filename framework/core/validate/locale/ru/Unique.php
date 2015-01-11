<?php

namespace rock\validate\locale\ru;


use rock\validate\locale\Locale;

class Unique extends Locale
{
    public function defaultTemplates()
    {
        return [
            self::MODE_DEFAULT => [
                self::STANDARD => $this->i18n->translate('unique'),
            ],
            self::MODE_NEGATIVE => [
                self::STANDARD => $this->i18n->translate('notUnique'),
            ]
        ];
    }

    public function defaultPlaceholders($value = null)
    {
        if (isset($value)) {
            if (is_array($value)) {
                $value = implode(',', $value);
            }
            $value = "{$value}";
        }
        return [
            'value' =>  $value
        ];
    }
}