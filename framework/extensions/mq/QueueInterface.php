<?php

namespace rock\mq;


interface QueueInterface 
{
    /**
     * Determines if message can be sent.
     * @return boolean
     */
    public function beforeSend();

    /**
     * Called after sending the message.
     * @param mixed $result - expected result
     */
    public function afterSend(&$result = null);

    /**
     * Determines if message can be sent to specified subscription.
     *
     * @param string $topic - a subscription to a topic
     * @param string $message - send message
     * @return boolean
     */
    public function beforeSubscription($topic, $message = null);

    /**
     * Called after sending the message to a subscription.
     *
     * @param string $topic - a subscription to a topic
     * @param string $message - send message
     */
    public function afterSubscription($topic, $message = null);

    /**
     * Sends message to the queue in the background.
     *
     * @param string $message - send message
     */
    public function sendBackground($message);

    /**
     * Sends message to the queue.
     *
     * @param string $message - send message
     * @param int $limit - the number of iterations, defaults to -1 which means no limit
     * @throws MQException
     * @return null|string
     */
    public function send($message = '', $limit = -1);

    /**
     * Gets available messages from the queue (worker).
     *
     * @param callable $callback
     * @param int $limit - the number of iterations, defaults to -1 which means no limit
     */
    public function receive(callable $callback, $limit = -1);

    /**
     * Subscribes a recipient to this queue.
     *
     * @param string  $topic - a subscription to a topic
     * @param int $limit - the number of iterations, defaults to -1 which means no limit
     * @param string $message - send message
     * @return
     */
    public function subscribe($topic = '', $limit = -1, $message = '');

    /**
     * Publishes a message to this queue.
     *
     * @param \Closure[] $topics - receiving messages selectively (by topics).
     * @param int $limit - the number of iterations, defaults to -1 which means no limit
     *
     * ```php
     * $callbackFoo = function(\ZMQSocket $server){
     *   $server->send('I am server: ' . 'foo');
     * };
     * $callbackBar = function(\ZMQSocket $server){
     *   $server->send('I am server: ' . 'bar');
     * };
     * $zero = new \rock\mq\ZeroQueue();
     * $zero->publish(['foo' => $callbackFoo, 'bar' => $callbackBar]);
     * ```
     */
    public function publish(array $topics, $limit = -1);
}