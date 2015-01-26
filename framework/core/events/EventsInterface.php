<?php

namespace rock\events;


use rock\base\ObjectInterface;
use rock\db\ActiveQueryInterface;

interface EventsInterface extends ObjectInterface
{
    /**
     * Subscribing in event
     *
     * @param string $name name of event
     * @return static|ActiveQueryInterface
     */
    public function trigger($name);

    /**
     * Publishing event
     *
     * @param string         $name    name of event
     * @param array|\Closure $handler handler
     * @param null           $args
     * @param bool           $append
     * @return static|ActiveQueryInterface
     */
    public function on($name, $handler, $args = null, $append = true);

    /**
     * Detach event
     *
     * @param string $name name of event
     * @return static|ActiveQueryInterface
     */
    public function off($name);
}