<?php
namespace rock\user;

use rock\base\CollectionInterface;
use rock\base\ObjectInterface;
use rock\base\ObjectTrait;
use rock\cookie\Cookie;
use rock\helpers\ArrayHelper;
use rock\helpers\Instance;
use rock\request\Request;
use rock\Rock;
use rock\session\Session;

class User implements \ArrayAccess, CollectionInterface, ObjectInterface
{
    use ObjectTrait;

    /**
     * The adapter where to store the token: cookies or session (by default).
     * @var string|array|Session
     */
    public $storage = 'session';
    /**
     * Session key as container.
     * @var string
     */
    public $container = 'user';
    /**
     * @var string the session variable name used to store the
     * value of @see getReturnUrl() .
     */
    public $returnUrlParam = '__returnUrl';
    /** @var  Request */
    public $request = 'request';

    public function init()
    {
        $this->request = Instance::ensure($this->request);
        $this->storage = Instance::ensure($this->storage);
        if ($this->storage instanceof Cookie) {
            $this->storage->httpOnly = true;
        }
    }

    /**
     * @return boolean whether the user session has started
     */
    public function getIsActive()
    {
        return $this->storage->exists("{$this->container}.id");
    }

    /**
     * @inheritdoc
     */
    public function get($keys, $default = null)
    {
        if (!$this->getIsActive()) {
            return null;
        }

        return $this->storage->get($this->prepareKeys($keys), $default);
    }

    /**
     * @inheritdoc
     */
    public function offsetGet($keys)
    {
        return $this->get($keys);
    }

    /**
     * @inheritdoc
     */
    public function __get($keys)
    {
        return $this->get($keys);
    }

    /**
     * @inheritdoc
     */
    public function getAll(array $only = [], array $exclude = [])
    {
        if (!$this->getIsActive()) {
            return null;
        }
        return ArrayHelper::only($this->storage->get($this->container), $only, $exclude);
    }

    /**
     * @inheritdoc
     */
    public function getIterator(array $only = [], array $exclude = [])
    {
        if (!$array = $this->getAll($only, $exclude)) {
            return null;
        }

        return new \ArrayIterator($array);
    }

    /**
     * {@inheritdoc}
     */
    public function add($keys, $value)
    {
        if (!isset($value)) {
            return;
        }
        $this->storage->add($this->prepareKeys($keys), $value);
    }

    /**
     * {@inheritdoc}
     */
    public function __set($keys, $value)
    {
        $this->add($keys, $value);
    }

    /**
     * @inheritdoc
     */
    public function offsetSet($keys, $value)
    {
        $this->add($keys, $value);
    }

    /**
     * @inheritdoc
     */
    public function addMulti(array $names)
    {
        foreach ($names as $keys => $value) {
            $this->add($keys, $value);
        }
    }

    /**
     * @inheritdoc
     */
    public function exists($keys)
    {
        return (bool)$this->get($keys);
    }

    /**
     * @inheritdoc
     */
    public function offsetExists($keys)
    {
        return $this->exists($keys);
    }

    public function __isset($name)
    {
        return $this->exists($name);
    }

    /**
     * @inheritdoc
     */
    public function count()
    {
        return !$this->getIsActive() ? 0 : count($this->storage->get($this->container));
    }

    /**
     * @inheritdoc
     */
    public function getCount()
    {
        return $this->count();
    }

    /**
     * @inheritdoc
     */
    public function remove($keys)
    {
        if (!$this->getIsActive()) {
            return;
        }

        $this->storage->remove($this->prepareKeys($keys));
    }

    /**
     * @inheritdoc
     */
    public function offsetUnset($keys)
    {
        $this->remove($keys);
    }

    public function __unset($keys)
    {
        $this->remove($keys);
    }

    /**
     * @inheritdoc
     */
    public function removeMulti(array $names)
    {
        if (!$this->getIsActive()) {
            return;
        }
        foreach ($names as $name) {
            $this->remove($name);
        }
    }

    /**
     * @inheritdoc
     */
    public function removeAll()
    {
        $this->storage->remove($this->container);
    }

    /**
     * Is guest.
     *
     * @return bool
     */
    public function isGuest()
    {
        return !$this->isLogged();
    }

    /**
     * Is logged.
     *
     * @return bool
     */
    public function isLogged()
    {
        return (bool)$this->get('is_login');
    }

    /**
     * Login user.
     */
    public function login()
    {
        if (!$this->getIsActive()) {
            return;
        }
        $this->add('is_login', 1);
    }

    /**
     * Logout user.
     * @param bool $destroy destroy session.
     */
    public function logout($destroy = true)
    {
        if ($destroy === true && $this->storage instanceof Session) {
            $this->storage->destroy();
            return;
        }

        $this->removeAll();
    }

    protected static $access = [];

    /**
     * Check of compliance of the user to the role or permission.
     * @param string $roleName name of role/permission
     * @param array $params
     * @param bool  $allowCaching
     * @return bool
     */
    public function check($roleName, array $params = null, $allowCaching = true)
    {
        if (!$this->getIsActive()) {
            return false;
        }
        if ($allowCaching && empty($params) && isset(static::$access[$roleName])) {
            return static::$access[$roleName];
        }
        return static::$access[$roleName] =  Rock::$app->rbac->check($this->get('id'), $roleName, $params);
    }

    /**
     * Returns the URL that the browser should be redirected to after successful login.
     *
     * This method reads the return URL from the session. It is usually used by the login action which
     * may call this method to redirect the browser to where it goes after successful authentication.
     *
     * @param string|array $defaultUrl the default return URL in case it was not set previously.
     * If this is null and the return URL was not set previously, {@see \rock\request\Request::getHomeUrl()} will be redirected to.
     * @return string the URL that the user should be redirected to after login.
     */
    public function getReturnUrl($defaultUrl = null)
    {
        $url = $this->storage->get($this->returnUrlParam, $defaultUrl);

        return $url === null ?  $this->request->getHomeUrl() : $url;
    }

    /**
     * Remembers the URL in the session so that it can be retrieved back later by {@see \rock\user\User::getReturnUrl()}.
     * @param string|array $url the URL that the user should be redirected to after login.
     */
    public function setReturnUrl($url)
    {
        $this->storage->add($this->returnUrlParam, $url);
    }

    protected function prepareKeys($keys)
    {
        if (is_array($keys)) {
            array_unshift($keys, $this->container);
            return $keys;
        }
        if (is_string($keys)) {
            return "{$this->container}.{$keys}";
        }

        return $keys;
    }
}