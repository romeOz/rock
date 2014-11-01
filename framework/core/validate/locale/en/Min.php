<?php

namespace rock\validate\locale\en;


use rock\date\DateTime;
use rock\validate\locale\Locale;

class Min extends Locale
{
    const INCLUSIVE = 1;

    public function defaultTemplates()
    {
        return [
            self::MODE_DEFAULT => [
                self::STANDARD => $this->i18n->translate('min'),
                self::INCLUSIVE => $this->i18n->translate('minInclusive'),
            ],
            self::MODE_NEGATIVE => [
                self::STANDARD => $this->i18n->translate('notMin'),
                self::INCLUSIVE => $this->i18n->translate('notMinInclusive'),
            ]
        ];
    }

    public function defaultPlaceholders($minValue = null, $inclusive = false)
    {
        $this->defaultTemplate = $inclusive ? static::INCLUSIVE : static::STANDARD;
        if ($minValue instanceof \DateTime) {
            $minValue = $minValue->format('Y-m-d H:i:s');
        } elseif ($minValue instanceof DateTime) {
            $minValue = $minValue->format($minValue->format);
        }
        return [
            'name' => 'value',
            'minValue' =>  $minValue
        ];
    }
}