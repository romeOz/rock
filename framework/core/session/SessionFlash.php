<?php

namespace rock\session;


use rock\base\ComponentsInterface;
use rock\base\ComponentsTrait;

abstract class SessionFlash implements ComponentsInterface
{
    use ComponentsTrait {
        ComponentsTrait::__construct as parentConstruct;
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
     * Returns a flash message.
     *
     * A flash message is available only in the current request and the next request.
     *
     * @param string  $key     the key identifying the flash message
     * @param mixed   $defaultValue value to be returned if the flash message does not exist.
     * @param boolean $delete  whether to delete this flash message right after this method is called.
     *                         If false, the flash message will be automatically deleted after the next request.
     * @param int     $counter
     * @return mixed the flash message
     * @see setFlash()
     * @see hasFlash()
     * @see getAllFlashes()
     * @see removeFlash()
     */
    public function getFlash($key, $defaultValue = null, $delete = false, &$counter = 0)
    {
        $counters = $this->get($this->flashParam, []);
        if (isset($counters[$key])) {
            $counter = $counters[$key];
            $value = $this->get($key, $defaultValue);
            if ($delete) {
                $this->removeFlash($key);
            } elseif ($counters[$key] < 0) {
                // mark for deletion in the next request
                $counters[$key] = 1;
                $this->add($this->flashParam, $counters);
            }

            return $value;
        } else {
            return $defaultValue;
        }
    }

    /**
     * Returns all flash messages.
     *
     * You may use this method to display all the flash messages in a view file:
     *
     * ```php
     * <?php
     * foreach(Rock::$app->session->getAllFlashes() as $key => $message) {
     *     echo '<div class="alert alert-' . $key . '">' . $message . '</div>';
     * } ?>
     * ```
     *
     * With the above code you can use the [bootstrap alert][] classes such as `success`, `info`, `danger`
     * as the flash message key to influence the color of the div.
     *
     * [bootstrap alert]: http://getbootstrap.com/components/#alerts
     *
     * @param boolean $delete whether to delete the flash messages right after this method is called.
     * If false, the flash messages will be automatically deleted in the next request.
     * @return array flash messages (key => message).
     * @see setFlash()
     * @see getFlash()
     * @see hasFlash()
     * @see removeFlash()
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
     * Stores a flash message.
     * A flash message will be automatically deleted after it is accessed in a request and the deletion will happen
     * in the next request.
     * @param string $key the key identifying the flash message. Note that flash messages
     * and normal session variables share the same name space. If you have a normal
     * session variable using the same name, its value will be overwritten by this method.
     * @param mixed $value flash message
     * @param boolean $removeAfterAccess whether the flash message should be automatically removed only if
     * it is accessed. If false, the flash message will be automatically removed after the next request,
     * regardless if it is accessed or not. If true (default value), the flash message will remain until after
     * it is accessed.
     * @see getFlash()
     * @see removeFlash()
     */
    public function setFlash($key, $value = true, $removeAfterAccess = false)
    {
        $counters = $this->get($this->flashParam, []);
        $counters[$key] = $removeAfterAccess ? -1 : 0;
        $this->add($key, $value);
        $this->add($this->flashParam, $counters);
    }

    /**
     * Removes a flash message.
     * @param string $key the key identifying the flash message. Note that flash messages
     * and normal session variables share the same name space.  If you have a normal
     * session variable using the same name, it will be removed by this method.
     * @return mixed the removed flash message. Null if the flash message does not exist.
     * @see getFlash()
     * @see setFlash()
     * @see removeAllFlashes()
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
     * Removes all flash messages.
     * Note that flash messages and normal session variables share the same name space.
     * If you have a normal session variable using the same name, it will be removed
     * by this method.
     * @see getFlash()
     * @see setFlash()
     * @see removeFlash()
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
     * Returns a value indicating whether there is a flash message associated with the specified key.
     *
     * @param string $key key identifying the flash message
     * @param bool   $isCounter
     * @return boolean whether the specified flash message exists
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