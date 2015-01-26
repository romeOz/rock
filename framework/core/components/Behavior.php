<?php

namespace rock\components;


use rock\base\ObjectInterface;
use rock\base\ObjectTrait;
use rock\db\BaseActiveRecord;

class Behavior implements ObjectInterface
{
    use ObjectTrait;

    /**
     * @var ComponentsInterface|BaseActiveRecord the owner of this behavior
     */
    public $owner;

    /**
     * Declares event handlers for the {@see \rock\components\Behavior::$owner}'s events.
     *
     * Child classes may override this method to declare what PHP callbacks should
     * be attached to the events of the {@see \rock\components\Behavior::$owner} component.
     *
     * The callbacks will be attached to the {@see  \rock\components\Behavior::$owner}'s events when the behavior is
     * attached to the owner; and they will be detached from the events when
     * the behavior is detached from the component.
     *
     * The callbacks can be any of the followings:
     *
     * - method in this behavior: `'handleClick'`, equivalent to `[$this, 'handleClick']`
     * - object method: `[$object, 'handleClick']`
     * - static method: `['Page', 'handleClick']`
     * - anonymous function: `function ($event) { ... }`
     *
     * The following is an example:
     *
     * ```php
     * [
     *     Model::EVENT_BEFORE_VALIDATE => 'myBeforeValidate',
     *     Model::EVENT_AFTER_VALIDATE => 'myAfterValidate',
     * ]
     * ```
     *
     * @return array events (array keys) and the corresponding event handler methods (array values).
     */
    public function events()
    {
        return [];
    }

    /**
     * Attaches the behavior object to the component.
     *
     * The default implementation will set the {@see \rock\components\Behavior::$owner} property
     * and attach event handlers as declared in {@see \rock\components\Behavior::events()}.
     * Make sure you call the parent implementation if you override this method.
     * @param ComponentsInterface $owner the component that this behavior is to be attached to.
     */
    public function attach($owner)
    {
        $this->owner = $owner;
        foreach ($this->events() as $event => $handler) {
            $owner->on($event, is_string($handler) ? [$this, $handler] : $handler);
        }
    }

    /**
     * Detaches the behavior object from the component.
     *
     * The default implementation will unset the {@see \rock\components\Behavior::$owner} property
     * and detach event handlers declared in {@see \rock\components\Behavior::events()}.
     * Make sure you call the parent implementation if you override this method.
     */
    public function detach()
    {
        if ($this->owner) {
            foreach ($this->events() as $event => $handler) {
                $this->owner->off($event, is_string($handler) ? [$this, $handler] : $handler);
            }
            $this->owner = null;
        }
    }
} 