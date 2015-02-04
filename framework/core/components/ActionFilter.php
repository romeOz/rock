<?php

namespace rock\components;


use rock\core\Controller;

class ActionFilter extends Behavior
{
    /**
     * @var array list of action IDs that this filter should apply to. If this property is not set,
     * then the filter applies to all actions, unless they are listed in {@see \rock\components\ActionFilter::$except}.
     * If an action ID appears in both {@see \rock\components\ActionFilter::$only} and {@see \rock\components\ActionFilter::$except}, this filter will NOT apply to it.
     *
     * Note that if the filter is attached to a module, the action IDs should also include child module IDs (if any)
     * and controller IDs.
     *
     * @see except
     */
    public $only = [];
    /**
     * @var array list of action IDs that this filter should not apply to.
     * @see only
     */
    public $except = [];
    protected $event;

    public function events()
    {
        if (class_exists('\rock\core\Controller')) {
            return [
                Controller::EVENT_BEFORE_ACTION => 'beforeFilter',
                Controller::EVENT_AFTER_ACTION => 'afterFilter'
            ];
        }
        return [];
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
//
//    /**
//     * @inheritdoc
//     */
//    public function detach()
//    {
//        if ($this->owner) {
//            $this->owner->off(Controller::EVENT_BEFORE_ACTION, [$this, 'beforeFilter']);
//            $this->owner->off(Controller::EVENT_AFTER_ACTION, [$this, 'afterFilter']);
//            $this->owner = null;
//        }
//    }

    /**
     * @param \rock\core\ActionEvent $event
     */
    public function beforeFilter($event)
    {
        if (!$this->isActive(isset($event->action) ? $event->action : null)) {
            return;
        }
        $this->event = $event;
        $this->event->isValid = $this->beforeAction(isset($this->event->action) ? $this->event->action : null);
        if ($event->isValid) {
            // call afterFilter only if beforeFilter succeeds
            // beforeFilter and afterFilter should be properly nested
            $this->owner->on(Controller::EVENT_AFTER_ACTION, [$this, 'afterFilter'], null, false);
        } else {
            $this->event->handled = true;
        }
    }

    /**
     * @param \rock\event\\rock\core\ActionEvent $event
     */
    public function afterFilter($event)
    {
        $this->event = $event;
        $event->result = $this->afterAction($this->event->action, $this->event->result);
        $this->owner->off(Controller::EVENT_AFTER_ACTION, [$this, 'afterFilter']);
    }

    /**
     * This method is invoked right before an action is to be executed (after all possible filters.)
     * You may override this method to do last-minute preparation for the action.
     * @param string $action the action to be executed.
     * @return boolean whether the action should continue to be executed.
     */
    public function beforeAction($action)
    {
        return true;
    }

    /**
     * This method is invoked right after an action is executed.
     * You may override this method to do some postprocessing for the action.
     * @param string $action the action just executed.
     * @param mixed $result the action execution result
     * @return mixed the processed action result.
     */
    public function afterAction($action, $result)
    {
        return $result;
    }

    /**
     * Returns a value indicating whether the filer is active for the given action.
     *
     * @param string $action the action being filtered
     * @return boolean whether the filer is active for the given action.
     */
    protected function isActive($action)
    {
        return !in_array($action, $this->except, true) && (empty($this->only) || in_array($action, $this->only, true));
    }
} 