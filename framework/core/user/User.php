<?php
namespace rock\user;

use apps\common\models\users\Users;
use rock\base\CollectionInterface;
use rock\base\ComponentsInterface;
use rock\base\ComponentsTrait;
use rock\base\ObjectTrait;
use rock\base\StorageInterface;
use rock\helpers\ArrayHelper;

class User implements \ArrayAccess, CollectionInterface, StorageInterface, ComponentsInterface
{
    use ComponentsTrait;

    public $adapterStorage = self::SESSION;
    /** @var  CollectionInterface */
    protected static $storage;
    public $container = 'user';


    public function init()
    {
//        if ($this->adapterStorage === self::COOKIE) {
//            $configs = $this->Rock->di->get(Cookie::className())['args'];
//            $configs['httpOnly'] = true;
//            static::$storage = new Cookie($configs);
//        } else {
//            static::$storage = $this->Rock->session;
//        }

        if (isset(static::$storage)) {
            return;
        }
        static::$storage = $this->Rock->session;
    }


    /**
     * @return boolean whether the user session has started
     */
    public function getIsActive()
    {
        return static::$storage->has("{$this->container}.id");
    }

    /**
     * @inheritdoc
     */
    public function get($keys, $default = null)
    {
        if (!$this->getIsActive()) {
            return null;
        }

        return static::$storage->get($this->prepareKeys($keys), $default);
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
    public function getMulti(array $names)
    {
        if (!$this->getIsActive()) {
            return null;
        }
        $result = [];
        foreach ($names as $keys) {
            if ($value = $this->get($keys)) {
                if (is_array($keys)) {
                    $keys = implode('.',$keys);
                }
                $result[$keys] = $value;
            }
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getAll(array $only = [], array $exclude = [])
    {
        if (!$this->getIsActive()) {
            return null;
        }
        return ArrayHelper::prepareArray(static::$storage->get($this->container), $only, $exclude);
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
        static::$storage->add($this->prepareKeys($keys), $value);
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
    public function has($keys)
    {
        return (bool)$this->get($keys);
    }

    /**
     * @inheritdoc
     */
    public function offsetExists($keys)
    {
        return $this->has($keys);
    }

    public function __isset($name)
    {
        return $this->has($name);
    }


    /**
     * @inheritdoc
     */
    public function count()
    {
        return !$this->getIsActive() ? 0 : count(static::$storage->get($this->container));
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

        static::$storage->remove($this->prepareKeys($keys));
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
        static::$storage->remove($this->container);
    }

    /**
     * Is guest user
     *
     * @return bool
     */
    public function isGuest()
    {
        return !$this->isAuthenticated();
    }

    /**
     * Is login user
     *
     * @return bool
     */
    public function isAuthenticated()
    {
        return (bool)$this->get('is_login');
    }

    public function login()
    {
        if (!$this->getIsActive()) {
            return;
        }
        $this->add('is_login', 1);
    }

    public function activate($token, $autoLogin = false)
    {
        if (empty($token) || (!$users = Users::findByToken($token))) {
            return false;
        }

        $users->removeToken();
        $users->setStatus(Users::STATUS_ACTIVE);
        $users->save();

        if ($autoLogin === true) {
            $this->addMulti($users->toArray());
            $this->login();
        }

        return true;
    }


    public function logout($destroy = true)
    {
        if ($destroy === true) {
            $this->Rock->session->destroy();
            return;
        }

        $this->removeAll();
    }

    protected static $access = [];


    public function check($roleName, array $params = null, $allowCaching = true)
    {
        if (!$this->getIsActive()) {
            return false;
        }
        if ($allowCaching && empty($params) && isset(static::$access[$roleName])) {
            return static::$access[$roleName];
        }
        return static::$access[$roleName] = $this->Rock->rbac->check($this->get('id'), $roleName, $params);
    }


    /**
     * Loads the number of allowed requests and the corresponding timestamp from a persistent storage.
     *
     * @param string $action e.g. FooController::actionIndex
     * @return array an array of two elements. The first element is the number of allowed requests,
     * and the second element is the corresponding UNIX timestamp.
     */
    public function loadAllowance($action)
    {
        if ((!$allowance = static::$storage->get($this->prepareKeys('_allowance'))) || !isset($allowance[$action])) {
            return [2, time()];
        }

        return [$allowance[$action]['maxRequests'], $allowance[$action]['timestamp']];
    }

    /**
     * Saves the number of allowed requests and the corresponding timestamp to a persistent storage.
     *
     * @param string        $action   e.g. FooController::actionIndex
     * @param integer $maxRequests the number of allowed requests remaining.
     * @param integer $timestamp   the current timestamp.
     */
    public function saveAllowance($action, $maxRequests, $timestamp)
    {
        $this->add(
            '_allowance',
            [
                $action => [
                    'maxRequests' => $maxRequests,
                    'timestamp' => $timestamp
                ]
            ]
        );
    }
    /**
     * Saves the number of allowed requests and the corresponding timestamp to a persistent storage.
     *
     * @param string        $action   e.g. FooController::actionIndex
     */
    public function removeAllowance($action)
    {
        $this->remove("_allowance.{$action}");
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