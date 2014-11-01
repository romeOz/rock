<?php

namespace rock\validate\locale\en;


use rock\validate\locale\Locale;

class Required extends Locale
{
    public function defaultTemplates()
    {
        return [
            self::MODE_DEFAULT => [
                self::STANDARD => $this->i18n->translate('required'),
            ],
            self::MODE_NEGATIVE => [
                self::STANDARD => $this->i18n->translate('notRequired'),
            ]
        ];
    }
}