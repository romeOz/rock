<?php

namespace rock\session;

use rock\events\EventsInterface;
use rock\events\EventsTrait;

abstract class SessionFlash implements EventsInterface, SessionInterface
{
    use EventsTrait {
        EventsTrait::__construct as parentConstruct;
    }

    /**
     * @var string the name of the session variable that stores the flash message data.
     */
    public $flashParam = '__flash';
    public static $skipUpdateFlash = false;

    /**
     * Updates the counters for flash messages and removes outdated flash messages.
     * This method should only be called once in {@see \rock\base\ObjectTrait::init()} .
     */
    protected function updateFlashCounters()
    {
        if (static::$skipUpdateFlash) {
            return;
        }
        $counters = $this->get($this->flashParam, []);
        if (is_array($counters)) {
            foreach ($counters as $key => $count) {
                if ($count > 0) {
                    unset($counters[$key]);
                    $this->remove($key);
                } elseif ($count == 0) {
                    $counters[$key]++;
                }
            }
            $this->add($this->flashParam, $counters);
        } else {
            // fix the unexpected problem that flashParam doesn't return an array
            $this->remove($this->flashParam);
        }
    }

    /**
     * @inheritdoc
     */
    public function getFlash($key, $default = null, $delete = false, &$counter = 0)
    {
        $counters = $this->get($this->flashParam, []);
        if (isset($counters[$key])) {
            $counter = $counters[$key];
            $value = $this->get($key, $default);
            if ($delete) {
                $this->removeFlash($key);
            } elseif ($counters[$key] < 0) {
                // mark for deletion in the next request
                $counters[$key] = 1;
                $this->add($this->flashParam, $counters);
            }

            return $value;
        } else {
            return $default;
        }
    }

    /**
     * @inheritdocn
     */
    public function getAllFlashes($delete = false)
    {
        $counters = $this->get($this->flashParam, []);
        $flashes = [];
        $all = $this->getAll();
        foreach (array_keys($counters) as $key) {
            if (array_key_exists($key, $all)) {
                $flashes[$key] = $this->get($key);
                if ($delete) {
                    unset($counters[$key]);
                    $this->remove($key);
                } elseif ($counters[$key] < 0) {
                    // mark for deletion in the next request
                    $counters[$key] = 1;
                }
            } else {
                unset($counters[$key]);
            }
        }

        $this->add($this->flashParam, $counters);
        return $flashes;
    }

    /**
     * @inheritdoc
     */
    public function setFlash($key, $value = true, $removeAfterAccess = false)
    {
        $counters = $this->get($this->flashParam, []);
        $counters[$key] = $removeAfterAccess ? -1 : 0;
        $this->add($key, $value);
        $this->add($this->flashParam, $counters);
    }

    /**
     * @inheritdoc
     */
    public function removeFlash($key)
    {
        $counters = $this->get($this->flashParam, []);
        $value = isset($this->$key, $counters[$key]) ? $this->get($key) : null;
        unset($counters[$key]);
        $this->remove($key);
        $this->add($this->flashParam, $counters);
        return $value;
    }

    /**
     * @inheritdoc
     */
    public function removeAllFlashes()
    {
        $counters = $this->get($this->flashParam, []);
        foreach (array_keys($counters) as $key) {
            $this->remove($key);
        }
        $this->remove($this->flashParam);
    }

    /**
     * @inheritdoc
     */
    public function hasFlash($key, $isCounter = false)
    {
        //return $this->getFlash($key) !== null;
        $result = $this->getFlash($key, null, false, $counter);

        if ($isCounter === true) {
            return $result !== null && ($counter === null || $counter == 0);
        }
        return $result !== null;
    }
}