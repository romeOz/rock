<?php

namespace rock\date\locale;

use rock\base\ClassName;

/**
 * Specify translations & formats for different locales
 */
abstract class Locale
{
    use ClassName;
    /** @var array */
    protected static $months;
    /** @var  array */
    protected static $shortMonths;
    /** @var array */
    protected static $weekDays;
    /** @var array */
    protected static $shortWeekDays;
    /** @var array */
    protected static $formats;

    public static function getFormats()
    {
        return static::$formats;
    }

    public static function getMonth($index)
    {
        return static::$months[$index];
    }
    public static function getShortMonth($index)
    {
        return static::$shortMonths[$index];
    }
    public static function getMonths()
    {
        return static::$months;
    }

    public static function getWeekDay($index)
    {
        return static::$weekDays[$index];
    }

    public static function getWeekDays()
    {
        return static::$weekDays;
    }

    public static function getShortWeekDay($index)
    {
        return static::$shortWeekDays[$index];
    }

    public static function getShortWeekDays()
    {
        return static::$shortWeekDays;
    }

    public static function getNamespace()
    {
        return __NAMESPACE__;
    }
}
