<?php

namespace rock\validate\locale\en;


use rock\validate\locale\Locale;

class Email extends Locale
{
    public function defaultTemplates()
    {
        return [
            self::MODE_DEFAULT => [
                self::STANDARD => $this->i18n->translate('email'),
            ],
            self::MODE_NEGATIVE => [
                self::STANDARD => $this->i18n->translate('notEmail'),
            ]
        ];
    }

    public function defaultPlaceholders()
    {
        return ['name' => 'email'];
    }
}