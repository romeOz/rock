<?php

namespace rock\date;


use DateTimeZone;
use rock\base\ObjectInterface;
use rock\base\ObjectTrait;
use rock\date\locale\En;
use rock\date\locale\EnUK;
use rock\date\locale\Locale;
use rock\date\locale\Ru;
use rock\date\locale\Ua;
use rock\di\Container;
use rock\i18n\i18nInterface;

/**
 * @method  date()
 * @method  time()
 * @method  datetime()
 */
class DateTime extends \DateTime implements i18nInterface, DateTimeInterface, ObjectInterface
{
    use ObjectTrait {
        ObjectTrait::__construct as parentConstruct;
    }

    const DEFAULT_FORMAT = 'Y-m-d H:i:s';

    /**
     * Default format: `Y-m-d H:i:s`.
     * @var string
     */
    public $format = self::DEFAULT_FORMAT;
    /**
     * Current locale.
     * @var string|callable
     */
    public $locale = i18nInterface::EN;
    /**
     * Locales.
     * @var array
     */
    public $locales = [];
    public $formats = [];

    /** @var  array */
    protected  static $formatsOption;
    protected static $defaultFormats = array(
        self::USER_DATE_FORMAT => 'm/d/Y',
        self::USER_TIME_FORMAT => 'g:i A',
        self::USER_DATETIME_FORMAT => 'm/d/Y g:i A',
        self::ISO_DATE_FORMAT => 'Y-m-d',
        self::ISO_TIME_FORMAT => 'H:i:s',
        self::ISO_DATETIME_FORMAT => 'Y-m-d H:i:s',
        self::JS_FORMAT => self::RFC1123,
        self::W3C_FORMAT=> self::W3C,

    );
    /** @var Locale */
    protected  static $localeObject;
    /** @var \DateTimezone[] */
    protected static  $timezonesObjects = [];
    protected static  $formatOptionsNames = [];
    protected static $formatOptionsPlaceholders = [];
    protected static $formatOptionsCallbacks = [];

    /**
     * @param string|int          $time
     * @param string|\DateTimeZone $timezone
     * @param array               $config
     */
    public function __construct($time = 'now', $timezone = null, array $config = [])
    {
        if (static::isTimestamp($time)) {
            $time = '@' . (string)$time;
        }
        $this->parentConstruct($config);

        parent::__construct($time, $this->calculateTimezone($timezone));

        if (is_callable($this->locale)) {
            $this->locale = call_user_func($this->locale, $this);
        }

        $this->formats = array_merge(static::$defaultFormats, $this->formats);
        $this->locales = array_merge($this->defaultLocales(), $this->locales);
        $this->initCustomFormatOptions();
        if (!empty(static::$formatsOption)) {
            foreach (static::$formatsOption as $alias => $callback) {
                $this->addFormatOption($alias, $callback);
            }
        }
    }

    /**
     * Set date for modify.
     *
     * @param string|int $time    time for modify
     * @param string|\DateTimeZone        $timezone
     * @param array       $config  the configuration. It can be either a string representing the class name
     *                             or an array representing the object configuration.
     * @throws \rock\di\ContainerException
     * @return $this
     */
    public static function set($time = 'now', $timezone = null, array $config = [])
    {
        if (!isset($time)) {
            $time = 'now';
        }
        $config['class'] = self::className();
        return Container::load($time, $timezone, $config);
    }

    /**
     * @return Locale
     */
    public function getLocale()
    {
        if (empty(static::$localeObject[$this->locale])) {

            if (!isset($this->locales[$this->locale])) {
                $this->locale = self::EN;
            }
            static::$localeObject[$this->locale] = new $this->locales[$this->locale];
        }

        return static::$localeObject[$this->locale];
    }

    /**
     * Set locale.
     *
     * @param string    $locale (e.g. en)
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    /**
     * Conversion in accordance with the client.
     *
     * @param string|DateTimeZone|null $timezone
     * @return $this|\DateTime
     */
    public function convertTimezone($timezone = null)
    {
        if (!isset($timezone)) {
            return $this;
        }
        return parent::setTimezone($this->calculateTimezone($timezone));
    }

    /**
     * Get formatting date.
     *
     * @param string|null $format http://php.net/date format or format name. Default value is current
     * @return string
     */
    public function format($format = null)
    {
        if (empty($format)) {
            $format = $this->format;
        }
        return $this->formatDatetimeObject($format);
    }

    /**
     * Get date in `YYYY-MM-DD` format, in server timezone.
     *
     * @return string
     */
    public function isoDate()
    {
        return $this->format(self::ISO_DATE_FORMAT);
    }

    /**
     * Get date in `HH-II-SS` format, in server timezone.
     *
     * @return string
     */
    public function isoTime()
    {
        return $this->format(self::ISO_TIME_FORMAT);
    }

    /**
     * Get datetime in `YYYY-MM-DD HH:II:SS` format, in server timezone.
     *
     * @return string
     */
    public function isoDatetime()
    {
        return $this->format(self::ISO_DATETIME_FORMAT);
    }

    /**
     * Magic call of $dater->format($datetimeOrTimestamp, $formatAlias).
     *
     * @param $formatAlias
     * @param $params
     * @throws DateException
     * @return string
     *
     * ```php
     * $datetime = new \rock\template\DateTime(time());
     * $datetime->addFormat('shortDate', 'd/m');
     * $datetime->shortDate();
     * ```
     */
    public function __call($formatAlias, $params)
    {
        $formatAlias = $this->getCustomFormat($formatAlias);
        if(!$formatAlias) {
            throw new DateException("There is no method or format with name: {$formatAlias}");
        }
        return $this->format($formatAlias);
    }

    /**
     * Validation exist date in the format of timestamp.
     *
     * @param string|int $timestamp
     * @return bool
     */
    public static function isTimestamp($timestamp)
    {
        if (is_bool($timestamp) || !is_scalar($timestamp)) {
            return false;
        }
        return ((string)(int)$timestamp === (string)$timestamp)
               && ($timestamp <= PHP_INT_MAX)
               && ($timestamp >= ~PHP_INT_MAX);
    }

    /**
     * Validate is date.
     *
     * @param string|int $date
     * @return bool
     */
    public static function is($date)
    {
        if (is_bool($date) || empty($date) xor ($date === 0 || $date === '0')) {
            return false;
        }
        $date = static::isTimestamp($date) ? '@' . (string)$date : $date;
        return (bool)date_create($date);
    }

    /**
     * Get microtime.
     *
     * @param int|null $microtime
     * @return float
     */
    public static function microtime($microtime = null)
    {
        list($usec, $sec) = explode(" ", $microtime ? : microtime());
        return (float)$usec + (float)$sec;
    }

    /**
     * Get millisecond.
     * @return float
     */
    public static function millitime()
    {
        return round(static::microtime() * 1000);
    }

    public function setDefaultFormat($format)
    {
        $this->format = $format;
        return $this;
    }

    /**
     * @param string $alias
     * @param string $format
     */
    public function addCustomFormat($alias, $format)
    {
        $this->formats[$alias] = $format;
    }

    /**
     * @param $alias
     * @return string|null
     */
    public function getCustomFormat($alias)
    {
        if (isset($this->formats[$alias])) {
            return $this->formats[$alias];
        }

        return null;
    }

    /**
     * @return array
     */
    public function getCustomFormats()
    {
        return $this->formats;
    }

    /**
     * @param string         $optionName
     * @param callable $callback
     * ```function (DataTime $dataTime) {}```
     * @throws DateException
     */
    public function addFormatOption($optionName, \Closure $callback)
    {
        if(in_array($optionName, static::$formatOptionsNames)) {
            return;
        }
        static::$formatOptionsNames[] = $optionName;
        static::$formatOptionsPlaceholders[] = '~' . count(static::$formatOptionsPlaceholders) . '~';
        static::$formatOptionsCallbacks[] = $callback;
    }

    /**
     * @param string|int|\DateTime $datetime2
     * @param bool      $absolute
     * @return bool|\DateInterval
     */
    public function diff($datetime2, $absolute = false)
    {
        if (is_scalar($datetime2)) {
            if (static::isTimestamp($datetime2)) {
                $datetime2 = '@' . (string)$datetime2;
            }
            $datetime2 = new \DateTime($datetime2);
        }
        if (($interval = parent::diff($datetime2, $absolute)) === false){
            return false;
        }
        $sign = $interval->invert;
        $days = $interval->days;
        // calculate seconds
        $seconds = $days * 24 * 60 * 60;
        $seconds += $interval->h * 60 * 60;
        $seconds += $interval->i * 60;
        $seconds += $interval->s;
        $interval->i = $this->addSign($sign, floor($seconds / 60));
        $interval->h = $this->addSign($sign, floor($seconds / (60 * 60)));
        $interval->d = $this->addSign($sign, $days);
        $interval->w = $this->addSign($sign, floor($days / 7));
        $interval->m = $this->addSign($sign, floor($days / 30));
        $interval->y = $this->addSign($sign, $interval->y);
        $interval->s = $this->addSign($sign, $seconds);
        return $interval;
    }

    protected function addSign($sign, $value)
    {
        return !$sign ? (int)$value : (int)$value * -1;
    }

    /**
     * Get @see \DateTimezone object by timezone name.
     *
     * @param string|\DateTimezone $timezone
     * @return \DateTimezone|null
     */
    protected function calculateTimezone($timezone)
    {
        if (!isset($timezone)) {
            return null;
        }

        $key = $timezone instanceof \DateTimeZone ? $timezone->getName() : $timezone;
        if(!isset(static::$timezonesObjects[$key])) {
            static::$timezonesObjects[$key] = is_string($timezone) ? new \DateTimezone($timezone) : $timezone;
        }
        return static::$timezonesObjects[$key];
    }

    /**
     * Format \DateTime object to http://php.net/date format or format name.
     *
     * @param $format
     * @return string
     */
    protected function formatDatetimeObject($format)
    {
        if ($format instanceof \Closure) {
            return call_user_func($format, $this);
        }
        $format = $this->getCustomFormat($format) ? : $format;

        if ($format instanceof \Closure) {
            return call_user_func($format, $this);
        }
        $isStashed = $this->stashCustomFormatOptions($format);
        $result = parent::format($format);
        if($isStashed) {
            $this->applyCustomFormatOptions($result);
        }
        return $result;
    }

    protected function initCustomFormatOptions()
    {
        $this->addFormatOption('F', function (DateTime $dateTime) {
            return $dateTime->getLocale()->getMonth($dateTime->format('n') - 1);
        });
        $this->addFormatOption('M', function (DateTime $dateTime) {
            return $dateTime->getLocale()->getShortMonth($dateTime->format('n') - 1);
        });
        $this->addFormatOption('l', function (DateTime $dateTime) {
            return $dateTime->getLocale()->getWeekDay($dateTime->format('N') - 1);
        });
        $this->addFormatOption('D', function (DateTime $dateTime) {
            return $dateTime->getLocale()->getShortWeekDay($dateTime->format('N') - 1);
        });
    }

    /**
     * Stash custom format options from standard PHP `\DateTime` format parser.
     *
     * @param $format
     * @return bool Return true if there was any custom options in $format
     */
    protected function stashCustomFormatOptions(&$format)
    {
        $format = str_replace(static::$formatOptionsNames, static::$formatOptionsPlaceholders, $format, $count);
        return (bool)$count;
    }

    /**
     * Stash custom format options from standard PHP `\DateTime` format parser.
     *
     * @param $format
     * @return bool Return true if there was any custom options in $format
     */
    protected function applyCustomFormatOptions(&$format)
    {
        $formatOptionsCallbacks = static::$formatOptionsCallbacks;
        $dateTime = $this;
        $format = preg_replace_callback('/~(\d+)~/', function ($matches) use ($dateTime, $formatOptionsCallbacks) {
            return call_user_func($formatOptionsCallbacks[$matches[1]], $dateTime);
        }, $format);
    }

    protected function defaultLocales()
    {
        return [
            'en' => En::className(),
            'en-UK' => EnUK::className(),
            'ru' => Ru::className(),
            'ua' => Ua::className(),
        ];
    }
}