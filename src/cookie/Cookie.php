<?php

namespace rock\cookie;


use rock\helpers\ArrayHelper;
use rock\helpers\Serialize;
use rock\helpers\SerializeInterface;
use rock\sanitize\Sanitize;
use rock\session\SessionFlash;

class Cookie extends SessionFlash implements \ArrayAccess, SerializeInterface
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
    /**
     * Default sanitize rules.
     * @var Sanitize
     */
    public $sanitize;

    protected static $isActive = false;

    public function init()
    {
        if (!static::$isActive) {
            $this->updateFlashCounters();
        }
        static::$isActive = true;
    }

    /**
     * @param string|array $keys        chain keys
     * @param mixed   $default
     * @param Sanitize  $sanitize
     * @return mixed|null
     */
    public function get($keys, $default = null, Sanitize $sanitize = null)
    {
        if (!$result = ArrayHelper::getValue(Serialize::unserializeRecursive($_COOKIE), $keys)) {
            return $default;
        }
        return $this->sanitize($result, $sanitize);
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
    public function getAll(array $only = [], array $exclude = [], Sanitize $sanitize = null)
    {
        if (empty($_COOKIE)) {
            return [];
        }
        static::$data = Serialize::unserializeRecursive($_COOKIE);
        static::$data = $this->sanitize(static::$data, $sanitize);

        return static::$data = ArrayHelper::only(static::$data, $only, $exclude);
    }

    /**
     *
     * @param array $only
     * @param array $exclude
     * @param Sanitize $sanitize
     * @return \ArrayIterator an iterator for traversing the cookies in the collection.
     */
    public function getIterator(array $only = [], array $exclude = [], Sanitize $sanitize = null)
    {
        return new \ArrayIterator($this->getAll($only, $exclude, $sanitize));
    }

    /**
     * @inheritdoc
     */
    public function add($name, $value)
    {
        if (is_array($value)) {
            $value = Serialize::serialize($value, $this->serializator);
        }
        if (!setcookie($name, $value, $this->expire, $this->path, $this->domain, $this->secure, $this->httpOnly)) {
            throw new CookieException(CookieException::INVALID_SET);
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
    public function exists($keys)
    {
        return (bool)$this->get($keys);
    }

    /**
     * @inheritdoc
     */
    public function offsetExists($name)
    {
        return $this->exists($name);
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
            throw new CookieException(CookieException::INVALID_SET);
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

    /**
     * Remove all values to $_COOKIES.
     */
    public function destroy()
    {
        $this->removeAll();
    }

    protected function sanitize($value, Sanitize $sanitize = null)
    {
        if (!isset($sanitize)) {
            if (isset($this->sanitize)) {
                $sanitize = $this->sanitize;
            } else {
                $sanitize = Sanitize::removeTags()->trim()->toType();
            }
            if (is_array($value)) {
                return Sanitize::attributes($sanitize)->sanitize($value);
            }
        }

        return $sanitize->sanitize($value);
    }

    public function reset($autoreset = false)
    {
        static::$isActive = false;
    }
}