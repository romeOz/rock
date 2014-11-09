<?php

namespace rock\session;


use rock\base\CollectionInterface;

interface SessionInterface extends CollectionInterface
{
    /**
     * Returns a flash message.
     *
     * A flash message is available only in the current request and the next request.
     *
     * @param string $key the key identifying the flash message
     * @param mixed $default value to be returned if the flash message does not exist.
     * @param boolean $delete whether to delete this flash message right after this method is called.
     * If false, the flash message will be automatically deleted after the next request.
     * @return mixed the flash message
     */
    public function getFlash($key, $default = null, $delete = false);

    /**
     * Returns all flash messages.
     * 
     * @return array flash messages (key => message).
     */
    public function getAllFlashes();

    /**
     * Stores a flash message.
     *
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
    public function setFlash($key, $value = true, $removeAfterAccess = true);

    /**
     * Removes a flash message.
     *
     * Note that flash messages will be automatically removed after the next request.
     * @param string $key the key identifying the flash message. Note that flash messages
     * and normal session variables share the same name space.  If you have a normal
     * session variable using the same name, it will be removed by this method.
     * @return mixed the removed flash message. Null if the flash message does not exist.
     */
    public function removeFlash($key);

    /**
     * Removes all flash messages.
     * Note that flash messages and normal session variables share the same name space.
     * If you have a normal session variable using the same name, it will be removed
     * by this method.
     */
    public function removeAllFlashes();

    /**
     * Returns a value indicating whether there is a flash message associated with the specified key.
     *
     * @param string $key key identifying the flash message
     * @param bool   $isCounter
     * @return boolean whether the specified flash message exists
     */
    public function hasFlash($key, $isCounter = false);
} 