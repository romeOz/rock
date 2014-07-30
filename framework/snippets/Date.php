<?php
namespace rock\snippets;

use rock\base\Snippet;
use rock\date\DateTime;
use rock\date\DateTimeInterface;
use rock\Rock;

/**
 * Snippet "DateView"
 *
 * Get formatted now date:
 * ```
 * [[Date
 *  ?format=`j n`
 * ]]
 * ```
 *
 * With default format:
 *
 * ```
 * [[Date
 *  ?date=`2014-02-12 15:01`
 *  ?format=`dmyhm`
 * ]]
 * ```
 */
/** @noinspection PhpHierarchyChecksInspection */
class Date extends Snippet implements DateTimeInterface
{
    /**
     * Datetime. `now` by default.
     * @var string
     */
    public $date = 'now';
    /**
     * Format of datetime.
     * @var string
     */
    public $format = DateTime::DEFAULT_FORMAT;
    public $timezone;
    public $config = [];

    public function get()
    {
        $this->config['class'] = DateTime::className();
        /** @var DateTime $dateTime */
        $dateTime = Rock::factory($this->date, null, $this->config);
        return $dateTime->convertTimezone($this->timezone)->format($this->format);
    }
}