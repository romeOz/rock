<?php

namespace rock\filters;


use rock\base\ObjectInterface;
use rock\event\Event;
use rock\Rock;

class EventFilter extends ActionFilter
{
    public $trigger = [];

    public $on = [];

    public $off = [];

    /** @var  Event */
    public $event;

    //public $result;

    public function before($action = null)
    {
        if (!$this->validateActions($action)) {
            return parent::before();
        }
        if (!$this->when || $this->when === self::BEFORE) {
            $this->attach();
        }
        return parent::before();
    }

    public function after(&$result = null, $action = null)
    {
        if (!$this->validateActions($action)) {
            return parent::after();
        }
        if ($this->when === self::AFTER) {
            $this->attach($result);
        }

        return parent::after();
    }

    protected function attach($result = null)
    {
        if (!isset($this->event)) {
            $this->event = new Event();
        }
        $this->event->result = $result;
        /** @var ObjectInterface $class */
        $class = $this->owner;
        foreach ((array)$this->off as $name) {
            Event::off($class::className(), $name);
        }

        foreach ($this->on as $name => $handlers) {
            foreach ($handlers as $handler) {
                Event::on($class::className(), $name, $handler);
            }
        }

        foreach ((array)$this->trigger as $name) {
            Event::trigger($class, $name, $this->event);
        }
    }
} 