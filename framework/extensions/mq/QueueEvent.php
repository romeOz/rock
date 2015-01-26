<?php
namespace rock\mq;

use rock\events\Event;

class QueueEvent extends Event
{
    /**
     * @var boolean whether the model is in valid status. Defaults to true.
     * A model is in valid status if it passes validations or certain checks.
     */
    public $isValid = true;
    public $id;
    public $message;
    public $topic;
} 