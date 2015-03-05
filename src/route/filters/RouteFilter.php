<?php

namespace rock\route\filters;


use rock\components\Behavior;
use rock\helpers\Helper;
use rock\route\Route;

class RouteFilter extends Behavior
{
    /**
     * Success as callable, when using filter.
     *
     * ```php
     * [[new Object, 'method'], $args]
     * [['Object', 'staticMethod'], $args]
     * [callback, $args]
     * ```
     *
     * @var array
     */
    public $success;
    /**
     * Fail as callable, when using filter.
     *
     * ```php
     * [[new Object, 'method'], $args]
     * [['Object', 'staticMethod'], $args]
     * [callback, $args]
     * ```
     *
     * @var array
     */
    public $fail;
    public $data;
    protected $event;

    public function events()
    {
        return [Route::EVENT_RULE_ROUTE => 'beforeFilter'];
    }

    /**
     * @inheritdoc
     */
    public function attach($owner)
    {
        $this->owner = $owner;
        foreach (array_keys($this->events(), 'beforeFilter', true) as $event) {
            $owner->on($event, [$this, 'beforeFilter']);
        }
    }

    /**
     * @param \rock\route\RouteEvent $event
     */
    public function beforeFilter($event)
    {
        $this->event = $event;
        $this->event->isValid = $this->before();
        if ($event->isValid) {
            // call afterFilter only if beforeFilter succeeds
            // beforeFilter and afterFilter should be properly nested
            $this->callback($this->success);
        } else {
            $event->handled = true;
            $this->callback($this->fail);
        }
    }

    /**
     * This method is invoked right before an route is to be executed (after all possible filters.)
     * @return boolean whether the action should continue to be executed.
     */
    public function before()
    {
        return true;
    }

    protected function callback($handler)
    {
        if (!isset($handler)) {
            return;
        }

        if ($handler instanceof \Closure) {
            $handler = [$handler];
        }
        $handler[1] = Helper::getValue($handler[1], [], true);
        list($function, $data) = $handler;
        $this->data = $data;
        call_user_func($function, $this);
    }
}