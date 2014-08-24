<?php
namespace rock\mq;

use rock\event\Event;

class QueueEvent extends Event
{
    public $id;
    public $message;
    public $topic;
} 