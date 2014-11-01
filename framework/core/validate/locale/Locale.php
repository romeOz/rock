<?php

namespace rock\validate\locale;


use rock\base\ClassName;
use rock\i18n\i18n;

abstract class Locale
{
    use ClassName;

    const STANDARD = 0;
    const NAMED = 1;
    const MODE_DEFAULT = 1;
    const MODE_NEGATIVE = 0;

    /** @var  i18n */
    public $i18n;
    public $defaultTemplate = self::STANDARD;

    abstract public function defaultTemplates();

    public function defaultPlaceholders()
    {
        return ['name' => 'value'];
    }
}