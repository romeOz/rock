<?php

namespace rock\date\locale;

use rock\date\DateTime;

class Ua extends Locale
{
    protected static $months = [
        'січня', 'лютого', 'березня', 'квітня', 'травня', 'червня', 'липня', 'серпня', 'вересня', 'жовтня', 'листопада',
        'грудня'
    ];
    protected static $weekDays = ['понеділок', 'вівторок', 'середа', 'четвер', "п'ятниця", 'субота', 'неділя'];
    protected static $shortWeekDays = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Нд'];

    protected static $formats = array(
        DateTime::USER_DATE_FORMAT => 'd.m.Y',
        DateTime::USER_TIME_FORMAT => 'G:i',
        DateTime::USER_DATETIME_FORMAT => 'd.m.Y G:i',
    );
}
