<?php

namespace rock\date;

/**
 * All this methods works through DateTime::__call method, mapped to format date with `DateTime::$formats[METHOD_NAME]` format:
 * @method date() Get date in Date::$formats['date'] format
 * @method time() Get date in Date::$formats['time'] format
 * @method datetime() Get date in Date::$formats['datetime'] format
 * @method isoDate() Get date in Date::$formats['isoDate'] format
 * @method isoTime() Get date in Date::$formats['isoTime'] format
 * @method isoDatetime() Get date in Date::$formats['isoDatetime'] format
 */
interface DateTimeInterface 
{
    const USER_DATE_FORMAT = 'date';
    const USER_TIME_FORMAT = 'time';
    const USER_DATETIME_FORMAT = 'datetime';
    const ISO_DATE_FORMAT = 'isoDate';
    const ISO_TIME_FORMAT = 'isoTime';
    const ISO_DATETIME_FORMAT = 'isoDatetime';
    const JS_FORMAT = 'js';
}