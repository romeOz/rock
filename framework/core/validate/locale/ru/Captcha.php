<?php

namespace rock\validate\locale\ru;


use rock\validate\locale\Locale;

/**
 * Class Captcha
 *
 * @codeCoverageIgnore
 * @package rock\validate\locale\ru
 */
class Captcha extends Locale
{
    public function defaultTemplates()
    {
        return [
            self::MODE_DEFAULT => [
                self::STANDARD => $this->i18n->translate('captcha'),
            ],
            self::MODE_NEGATIVE => [
                self::STANDARD => $this->i18n->translate('notCaptcha'),
            ]
        ];
    }
}