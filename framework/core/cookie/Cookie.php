<?php

namespace rock\cookie;


use rock\base\CollectionInterface;
use rock\helpers\ArrayHelper;
use rock\helpers\Sanitize;
use rock\helpers\Serialize;
use rock\helpers\SerializeInterface;
use rock\session\SessionFlash;
use rock\session\SessionInterface;

class Cookie extends SessionFlash implements \ArrayAccess, CollectionInterface, SerializeInterface, SessionInterface
{
    /**
     * @var array
     */
    protected static $data = [];
    /**
     * Serialize handler
     * @var int
     */
    public $serializator = self::SERIALIZE_PHP;
    /**
     * Filter sanitize
     * @var array
     */
    public $filter = [Sanitize::UNSERIALIZE, Sanitize::STRIP_TAGS, 'trim'];
    /**
     * @var string domain of the cookie
     */
    public $domain = '';
    /**
     * @var integer the timestamp at which the cookie expires. This is the server timestamp.
     * Defaults to 0, meaning "until the browser is closed".
     */
    public $expire = 0;
    /**
     * @var string the path on the server in which the cookie will be available on. The default is '/'.
     */
    public $path = '/';
    /**
     * @var boolean whether cookie should be sent via secure connection
     */
    public $secure = false;
    /**
     * @var boolean whether the cookie should be accessible only through the HTTP protocol.
     * By setting this property to true, the cookie will not be accessible by scripting languages,
     * such as JavaScript, which can effectively help to reduce identity theft through XSS attacks.
     */
    public $httpOnly = false;

    protected static $isActive = false;

    public function init()
    {
        if (!static::$isActive) {
            $this->updateFlashCounters();
        }
        static::$isActive = true;
    }


    /**
     * @param string|array $keys        - chain keys
     * @param mixed   $default
     * @param array  $filters
     * @return mixed|null
     */
    public function get($keys, $default = null, array $filters = null)
    {
        if (!$result = ArrayHelper::getValue(Serialize::unserializeRecursive($_COOKIE), $keys)) {
            return $default;
        }

        return Sanitize::sanitize($result, $filters);
    }

    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * @inheritdoc
     */
    public function offsetGet($name)
    {
        return $this->get($name);
    }

    /**
     * @inheritdoc
     */
    public function getMulti(array $names)
    {
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
    public function getAll(array $only = [], array $exclude = [], array $filters = null)
    {
        return ArrayHelper::prepareArray($this->prepare($filters), $only, $exclude);
    }

    /**
     *
     * @param array $only
     * @param array $exclude
     * @param array $filters
     * @return \ArrayIterator an iterator for traversing the cookies in the collection.
     */
    public function getIterator(array $only = [], array $exclude = [], array $filters = null)
    {
        return new \ArrayIterator($this->getAll($only, $exclude, $filters));
    }

    /**
     * @inheritdoc
     */
    public function add($name, $value)
    {
        $value = Sanitize::sanitize($value);
        if (is_array($value)) {
            $value = Serialize::serialize($value, $this->serializator);
        }
        if (!setcookie($name, $value, $this->expire, $this->path, $this->domain, $this->secure, $this->httpOnly)) {
            throw new Exception(Exception::CRITICAL, Exception::INVALID_SET);
        }
        $_COOKIE[$name] = $value;
    }

    /**
     * @inheritdoc
     */
    public function offsetSet($name, $value)
    {
        $this->add($name, $value);
    }

    public function __set($name, $value)
    {
        $this->add($name, $value);
    }

    /**
     * @inheritdoc
     */
    public function addMulti(array $data)
    {
        foreach ($data as $keys => $value)  {
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
    public function offsetExists($name)
    {
        return $this->has($name);
    }

    /**
     * @inheritdoc
     */
    public function count()
    {
        return $this->getCount();
    }

    /**
     * @inheritdoc
     */
    public function getCount()
    {
        return count($_COOKIE);
    }

    /**
     * @inheritdoc
     */
    public function remove($name)
    {
        if ($name === session_name()) {
            return;
        }
        if (!setcookie($name, '', time() - 3600, $this->path, $this->domain, $this->secure, $this->httpOnly)) {
            throw new Exception(Exception::CRITICAL, Exception::INVALID_SET);
        }
        unset($_COOKIE[$name]);
    }

    /**
     * @inheritdoc
     */
    public function offsetUnset($name)
    {
        $this->remove($name);
    }

    /**
     * @inheritdoc
     */
    public function removeMulti(array $names)
    {
        foreach ($names as $name) {
            $this->remove($name);
        }
    }

    /**
     * @inheritdoc
     */
    public function removeAll()
    {
        foreach ($_COOKIE as $name => $value) {
            $this->remove($name);
        }
    }

    protected function prepare(array $filters = null)
    {
        if (empty($_COOKIE)) {
            return [];
        }

        return static::$data = Sanitize::sanitize(Serialize::unserializeRecursive($_COOKIE), $filters);
    }
}