<?php

namespace rock\db;


use rock\event\Event;

class AfterFindEvent extends Event
{
    /**
     * @var mixed the query result.
     */
    public $result;
} 