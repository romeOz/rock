<?php

namespace rock\validate\locale\en;


use rock\validate\locale\Locale;

class Regex extends Locale
{
    public function defaultTemplates()
    {
        return [
            self::MODE_DEFAULT => [
                self::STANDARD => $this->i18n->translate('regex'),
            ],
            self::MODE_NEGATIVE => [
                self::STANDARD => $this->i18n->translate('notRegex'),
            ]
        ];
    }
}