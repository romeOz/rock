<?php

namespace rock\db;


use rock\events\Event;

class AfterFindEvent extends Event
{
    /**
     * @var mixed the query result.
     */
    public $result;
} 