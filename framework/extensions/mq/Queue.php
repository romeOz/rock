<?php

namespace rock\mq;


use rock\base\ComponentsInterface;
use rock\base\ComponentsTrait;
use rock\base\ModelEvent;
use rock\event\Event;

class Queue implements ComponentsInterface
{
    use ComponentsTrait;

    /**
     * @event Event an event that is triggered before a message is sent to a queue.
     */
    const EVENT_BEFORE_SEND = 'beforeSend';
    /**
     * @event Event an event that is triggered after a message is sent to a queue.
     */
    const EVENT_AFTER_SEND = 'afterSend';
    /**
     * @event Event an event that is triggered before a message is sent to a subscription.
     */
    const EVENT_BEFORE_SUBSCRIPTION = 'beforeSubscription';
    /**
     * @event Event an event that is triggered after a message is sent to a subscription.
     */
    const EVENT_AFTER_SUBSCRIPTION = 'afterSubscription';

    /**
     * @var string $id - id of the queue, required. Should be set to the component id.
     */
    public $id = 'queue';
    /**
     * @var integer $timeout - the number of seconds to wait for I/O
     */
    public $timeout = 1;
    /**
     * @var boolean $blocking - if true, when fetching messages, waits until a new message is sent if there are none in the queue. Does not determine blocking on sending.
     */
    public $blocking = false;

    /**
     * @inheritdoc
     */
    public function beforeSend()
    {
        $event = new ModelEvent();
        $this->trigger(self::EVENT_BEFORE_SEND, $event);
        return $event->isValid;
    }

    /**
     * @inheritdoc
     */
    public function afterSend(&$result = null)
    {
        $event = new ModelEvent();
        $event->result = $result;
        $this->trigger(self::EVENT_AFTER_SEND, $event);
        $result = $event->result;
    }

    /**
     * @inheritdoc
     */
    public function beforeSubscription($topic, $message = null)
    {
        $event = new QueueEvent(['id' => $this->id, 'message' => $message, 'topic' => $topic]);
        return $this->trigger(self::EVENT_BEFORE_SUBSCRIPTION, Event::BEFORE, $event)->before();
    }

    /**
     * @inheritdoc
     */
    public function afterSubscription($topic, $message = null)
    {
        $event = new QueueEvent(['id' => $this->id, 'message' => $message, 'topic' => $topic]);
        $this->trigger(self::EVENT_AFTER_SUBSCRIPTION, Event::AFTER, $event)->after();
    }
}