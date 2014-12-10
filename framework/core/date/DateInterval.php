<?php

namespace rock\date;


class DateInterval extends \DateInterval
{
    /**
     * Number of weeks
     * @var int
     */
    public $w = 0;
    /**
     * Total number of days the interval spans. If this is unknown, days will be FALSE.
     * @var int
     */
    public $_days = 0;

    public function __construct(\DateInterval $dateInterval)
    {
        $interval = "P{$dateInterval->y}Y{$dateInterval->m}M{$dateInterval->d}DT{$dateInterval->h}H{$dateInterval->i}M{$dateInterval->s}S";
        parent::__construct($interval);
        $this->_days = $dateInterval->days;
    }
}