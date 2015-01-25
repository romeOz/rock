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

    public $defaultTemplate = self::STANDARD;

    /**
     * List templates by default.
     * @return mixed
     */
    abstract public function defaultTemplates();

    /**
     * List placeholders by default.
     * @return array
     */
    public function defaultPlaceholders()
    {
        return ['name' => 'value'];
    }
}