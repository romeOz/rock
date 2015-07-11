<?php

namespace rock;


use rock\core\Properties;

class LocaleProperties extends Properties
{
    public static function locale()
    {
        return Rock::$app->language;
    }
}