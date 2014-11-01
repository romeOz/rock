<?php

namespace rock\validate\locale\ru;


use rock\validate\locale\Locale;

/**
 * Class Confirm
 *
 * @codeCoverageIgnore
 * @package rock\validate\locale\ru
 */
class Confirm extends Locale
{
    public function defaultTemplates()
    {
        return [
            self::MODE_DEFAULT => [
                self::STANDARD => $this->i18n->translate('confirm'),
            ],
            self::MODE_NEGATIVE => [
                self::STANDARD => $this->i18n->translate('notConfirm'),
            ]
        ];
    }
}