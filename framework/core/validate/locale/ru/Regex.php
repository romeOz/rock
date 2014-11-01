<?php

namespace rock\validate\locale\ru;


use rock\validate\locale\Locale;

/**
 * Class Regex
 *
 * @codeCoverageIgnore
 * @package rock\validate\locale\ru
 */
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

    public function defaultPlaceholders()
    {
        return ['name' => 'значение'];
    }
}