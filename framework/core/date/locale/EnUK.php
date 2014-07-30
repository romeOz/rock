<?php

namespace rock\date\locale;


use rock\date\DateTime;

class EnUK extends Locale
{
    protected static $months = [
        'January', 'February', 'March', 'April', 'May', 'June', 'Jule', 'August', 'September', 'October', 'November',
        'December'
    ];
    protected static $shortMonths = [
        'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
    ];
    protected static $weekDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    protected static $shortWeekDays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

    protected static $formats = [
        DateTime::USER_DATE_FORMAT => 'm/d/Y',
        DateTime::USER_TIME_FORMAT => 'G:i',
        DateTime::USER_DATETIME_FORMAT => 'm/d/Y G:i',
    ];
}
