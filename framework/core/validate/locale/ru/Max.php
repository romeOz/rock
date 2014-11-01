<?php

namespace rock\validate\locale\ru;


use rock\date\DateTime;
use rock\validate\locale\Locale;

/**
 * Class Max
 *
 * @codeCoverageIgnore
 * @package rock\validate\locale\ru
 */
class Max extends Locale
{
    const INCLUSIVE = 1;

    public function defaultTemplates()
    {
        return [
            self::MODE_DEFAULT => [
                self::STANDARD => $this->i18n->translate('max'),
                self::INCLUSIVE => $this->i18n->translate('maxInclusive'),
            ],
            self::MODE_NEGATIVE => [
                self::STANDARD => $this->i18n->translate('notMax'),
                self::INCLUSIVE => $this->i18n->translate('notMaxInclusive'),
            ]
        ];
    }

    public function defaultPlaceholders($maxValue = null, $inclusive = false)
    {
        $this->defaultTemplate = $inclusive ? static::INCLUSIVE : static::STANDARD;
        if ($maxValue instanceof \DateTime) {
            $maxValue = $maxValue->format('Y-m-d H:i:s');
        } elseif ($maxValue instanceof DateTime) {
            $maxValue = $maxValue->format($maxValue->format);
        }
        return [
            'name' => 'значение',
            'maxValue' =>  $maxValue
        ];
    }
}