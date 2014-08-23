<?php

namespace rock\db;


use rock\event\Event;

class AfterSaveEvent extends Event
{
    /**
     * @var array The attribute values that had changed and were saved.
     */
    public $changedAttributes;
} 