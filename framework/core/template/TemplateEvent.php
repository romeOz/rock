<?php

namespace rock\template;


use rock\event\Event;

class TemplateEvent extends Event
{
    /**
     * @var string the path to chunk/name of snippet
     */
    public $name;
    /**
     * @var mixed the action result. Event handlers may modify this property to change the action result.
     */
    public $result;
    /**
     * @var boolean whether the model is in valid status. Defaults to true.
     * A model is in valid status if it passes validations or certain checks.
     */
    public $isValid = true;
} 