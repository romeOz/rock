<?php

namespace rock\snippets\filters;


use rock\components\Behavior;
use rock\helpers\Helper;
use rock\snippets\Snippet;

class SnippetFilter extends Behavior
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
        return [
            Snippet::EVENT_BEFORE_SNIPPET => 'beforeFilter',
            Snippet::EVENT_AFTER_SNIPPET => 'afterFilter'
        ];
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
     * @param \rock\snippets\SnippetEvent $event
     */
    public function beforeFilter($event)
    {
        $this->event = $event;
        $this->event->isValid = $this->before();
        if ($event->isValid) {
            // call afterFilter only if beforeFilter succeeds
            // beforeFilter and afterFilter should be properly nested
            $this->owner->on(Snippet::EVENT_AFTER_SNIPPET, [$this, 'afterFilter'], null, false);
            $this->callback($this->success);
        } else {
            $event->handled = true;
            $this->callback($this->fail);
        }
    }

    /**
     * @param \rock\snippets\SnippetEvent $event
     */
    public function afterFilter($event)
    {
        $this->event = $event;
        $event->result = $this->after($this->event->result);
        $this->owner->off(Snippet::EVENT_AFTER_SNIPPET, [$this, 'afterFilter']);
    }

    /**
     * This method is invoked right before an snippet is to be executed (after all possible filters.)
     * @return boolean whether the action should continue to be executed.
     */
    public function before()
    {
        return true;
    }

    /**
     * This method is invoked right after an snippet is executed.
     * You may override this method to do some postprocessing for the snippet.
     * @param mixed $result the snippet execution result
     * @return mixed the processed snippet result.
     */
    public function after($result)
    {
        return $result;
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