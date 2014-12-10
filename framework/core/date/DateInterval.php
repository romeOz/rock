<?php

namespace rock\date;


class DateInterval extends \DateInterval
{
    /**
     * @var int
     */
    public $w;

    public function __construct(\DateInterval $dateInterval)
    {
        $interval = "PT{$dateInterval->s}S";
        parent::__construct($interval);
    }
}