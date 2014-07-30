<?php

namespace rock\date\locale;

use rock\date\DateTime;

class Ru extends Locale
{
    protected static $months = [
        'января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября',
        'декабря'
    ];
    protected static $shortMonths = [
        'янв', 'фев', 'мар', 'апр', 'май', 'июн', 'июл', 'авг', 'сен', 'окт', 'ноя', 'дек'
    ];
    protected static $weekDays = [
        'понедельник', 'вторник', 'среда', 'четверг', 'пятница', 'суббота', 'воскресенье'
    ];
    protected static $shortWeekDays = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];

    protected static $formats = [
        DateTime::USER_DATE_FORMAT => 'd.m.Y',
        DateTime::USER_TIME_FORMAT => 'G:i',
        DateTime::USER_DATETIME_FORMAT => 'd.m.Y G:i',
    ];
}
