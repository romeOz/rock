<?php
namespace rock\snippets;

use rock\core\Snippet;
use rock\date\DateTime;
use rock\date\DateTimeInterface;

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
        $dateTime = DateTime::set($this->date, null, $this->config);
        if (isset($this->timezone)) {
            $dateTime->convertTimezone($this->timezone);
        }
        return $dateTime->format($this->format);
    }
}