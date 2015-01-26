<?php
namespace rock\events;

use rock\base\Alias;
use rock\base\ObjectInterface;
use rock\base\ObjectTrait;
use rock\helpers\ArrayHelper;
use rock\helpers\Helper;
use rock\helpers\ObjectHelper;

class Event implements ObjectInterface
{
    use ObjectTrait;

    /** @var  string */
    public $name;
    /**
     * @var object the sender of this event. If not set, this property will be
     * set as the object whose "trigger()" method is called.
     * This property may also be a `null` when this event is a
     * class-level event which is triggered in a static context.
     */
    public $owner;
    /** @var  mixed */
    public $result;
    /** @var  mixed */
    public $data;
    /**
     * @var boolean whether the event is handled. Defaults to `false`.
     * When a handler sets this to be true, the event processing will stop and
     * ignore the rest of the uninvoked event handlers.
     */
    public $handled = false;

    /**
     * Array of events
     *
     * @var array
     */
    protected static $events = [];


    /**
     * Subscribing in event.
     *
     * @param string|object $class the object or the fully qualified class name specifying the class-level event.
     * @param string $name the event name.
     * @param Event $event the event parameter. If not set, a default [[Event]] object will be created.
     */
    public static function trigger($class, $name, $event = null)
    {
        if (empty(static::$events)) {
            return;
        }
        if ($event === null) {
            $event = new static;
        }
        $event->handled = false;
        $event->name = $name;

        if (is_object($class)) {
            if ($event->owner === null) {
                $event->owner = $class;
            }
            $class = get_class($class);
        } else{
            $class = ltrim($class, '\\');
        }

        do {
            if (!empty(static::$events[$class][$name])) {
                foreach (static::$events[$class][$name] as $handler) {
                    list($function, $data) = $handler;
                    $event->data = $data;
                    call_user_func($function, $event);
                    if ($event->handled) {
                        return;
                    }
                }
                //static::off($class, $name);
            }
        } while (($class = get_parent_class($class)) !== false);
    }


    /**
     * Publishing event.
     *
     * @param string|object $class the fully qualified class name to which the event handler needs to attach.
     * @param string         $name          name of event
     * @param array|\Closure $handler       handler
     *
     * - `[function ($args) { ... }, $data]`
     * - `[$instance, 'method', $data]`
     * - `['Class', 'static_method', $data]`
     *
     */
    public static function on($class, $name, $handler)
    {
        $class = ObjectHelper::getClass($class);
        if (!isset(static::$events[$class][$name])) {
            static::$events[$class][$name] = [];
        }

        if ($handler instanceof \Closure) {
            $handler = [$handler];
        }

        $handler[1] = Helper::getValue($handler[1], [], true);
        list($function, $data) = $handler;
        static::$events[$class][$name][] = [self::_calculateHandler($function), $data];
    }


    /**
     * Detach event.
     *
     * @param string|object   $class   the fully qualified class name from which the event handler needs to be detached.
     * @param string   $name    name of event
     * @param callable $handler the event handler to be removed.
     *                          If it is null, all handlers attached to the named event will be removed.
     * @return bool
     * @see on()
     */
    public static function off($class, $name, $handler = null)
    {
        $class = ObjectHelper::getClass($class);
        if ($handler === null) {
            unset(static::$events[$class][$name]);
            if (empty(static::$events[$class])) {
                unset(static::$events[$class]);
            }
            return true;
        }

        $removed = false;
        foreach (self::$events[$class][$name] as $i => $event) {
            if ($event === $handler) {
                unset(self::$events[$class][$name][$i]);
                $removed = true;
            }
        }
        if ($removed) {
            self::$events[$class][$name] = array_values(self::$events[$class][$name]);
        }

        return $removed;
    }

    /**
     * Detach events
     *
     * ```php
     * $events = [
     *  ['rock\\db\\Query', \rock\db\Query::EVENT_AFTER_FIND]
     * ];
     * \rock\events\Event::offMulti($events);
     * ```
     *
     * @param array $names - names of event
     */
    public static function offMulti(array $names)
    {
        foreach ($names as $value) {
            list($class, $name) = $value;
            static::off($class, $name);
        }
    }

    /**
     * Detach all event
     */
    public static function offAll()
    {
        static::$events = [];
    }

    /**
     * @param string|object $class
     */
    public static function offClass($class)
    {
        $class = ObjectHelper::getClass($class);
        do {
            unset(static::$events[$class]);
        } while (($class = get_parent_class($class)) !== false);
    }


    /**
     * Get event
     *
     * @param string|object       $class
     * @param string $name - name of event
     * @return array|null
     */
    public static function get($class, $name)
    {
        $class = ObjectHelper::getClass($class);
        return !empty(static::$events[$class][$name]) ? static::$events[$class][$name] : null;
    }


    /**
     * @inheritdoc
     */
    public static function getAll(array $only = [], array $exclude = [])
    {
        return ArrayHelper::only(static::$events, $only, $exclude);
    }


    /**
     * Exists event
     *
     * @param string|object       $class
     * @param string $name - name of event
     * @return bool
     */
    public static function has($class, $name)
    {
        $class = ObjectHelper::getClass($class);
        do {
            if (!empty(self::$events[$class][$name])) {
                return true;
            }
        } while (($class = get_parent_class($class)) !== false);

        return false;
    }


    /**
     * Total count of events
     * @return int
     */
    public static function count()
    {
        return count(static::$events);
    }

    /**
     * Count handlers of events.
     *
     * @param string|object $class
     * @param string $name name of event
     * @return int
     */
    public static function countHandlers($class, $name)
    {
        $class = ObjectHelper::getClass($class);
        $count = 0;

        do {
            if (!empty(self::$events[$class][$name])) {
                $count += count(static::$events[$class][$name]);
            }
        } while (($class = get_parent_class($class)) !== false);

        return $count;
    }

    /**
     * Count events of class (with parents).
     *
     * @param string|object $class
     * @return int
     */
    public static function countClass($class)
    {
        $class = ObjectHelper::getClass($class);
        $count = 0;
        do {
            if (!empty(self::$events[$class])) {
                $count += count(static::$events[$class]);
            }
        } while (($class = get_parent_class($class)) !== false);

        return $count;
    }


    private static function _calculateHandler($function)
    {
        if (is_array($function) && is_string($function[0])) {
            $function[0] = Alias::getAlias($function[0]);
        }

        return $function;
    }
}