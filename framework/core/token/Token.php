<?php
namespace rock\token;


use rock\base\CollectionInterface;
use rock\base\ComponentsTrait;
use rock\base\ObjectTrait;
use rock\base\StorageInterface;
use rock\cookie\Cookie;
use rock\helpers\ArrayHelper;
use rock\request\RequestInterface;
use rock\session\SessionInterface;

class Token implements \ArrayAccess,CollectionInterface, RequestInterface, StorageInterface
{
    use ComponentsTrait;

    /**
     * The name of the HTTP header for sending CSRF token.
     */
    const CSRF_HEADER = 'X-CSRF-Token';



    public $adapterStorage = self::SESSION;
    /** @var  SessionInterface */
    protected static $storage;
    /**
     * @var boolean whether to enable CSRF (Cross-Site Request Forgery) validation. Defaults to true.
     * When CSRF validation is enabled, forms submitted to an Rock Web application must be originated
     * from the same application. If not, a 400 HTTP exception will be raised.
     *
     * Note, this feature requires that the user client accepts cookie. Also, to use this feature,
     * forms submitted via POST method must contain a hidden input whose name is specified by [[csrfVar]].
     * You may use `\rock\helpers\Html::beginForm()` to generate his hidden input.
     *
     * @see Controller::enableCsrfValidation
     * @see http://en.wikipedia.org/wiki/Cross-site_request_forgery
     */
    public $enableCsrfValidation = true;
    /**
     * @var string the name of the token used to prevent CSRF. Defaults to '_csrf'.
     * This property is used only when [[enableCsrfValidation]] is true.
     */
    public $csrfPrefix = 'csrf';

    protected $exception = [];


    public function init()
    {
        if (isset(static::$storage)) {
            return;
        }
        if ($this->adapterStorage === self::COOKIE) {
            $configs = $this->Rock->di->get(Cookie::className())['args'];
            $configs['httpOnly'] = true;
            static::$storage = new Cookie($configs);
        } else {
            static::$storage = $this->Rock->session;
        }
    }



    /**
     * Create csrf-token
     *
     * @param string $name - name of token
     * @return string
     */
    public function create($name)
    {
        if ($this->enableCsrfValidation === false) {
            return null;
        }
        $name = $this->addPrefix($name);
        $key = $this->Rock->security->generateRandomKey();
        static::$storage->setFlash($name, $key, false);

        return $key;
    }


    public function addPrefix($name)
    {
        if (isset($this->csrfPrefix)) {
            return $this->csrfPrefix . $name;
        }

        return $name;
    }


    /**
     * Validation token
     *
     * @param string $name  - name of token
     * @param string $token - value of token
     * @return bool
     */
    public function valid($name, $token = null)
    {
        if ($this->enableCsrfValidation === false) {
            return true;
        }
        if (!empty($token)) {
            if ($this->get($name) === $token) {
                $this->remove($name);
                return true;
            }
        }

        return false;
    }

    /**
     * @return string the CSRF token sent via [[CSRF_HEADER]] by browser. Null is returned if no such header is sent.
     */
    public function getCsrfTokenFromHeader()
    {
        $key = 'HTTP_' . str_replace('-', '_', strtoupper(self::CSRF_HEADER));

        return isset($_SERVER[$key]) ? $_SERVER[$key] : null;
    }

    /**
     * Removes a data resource.
     *
     * @param $name
     */
    public function remove($name)
    {
        static::$storage->removeFlash($this->addPrefix($name));
    }


    /**
     * Returns an iterator for traversing the tokens in the collection.
     * This method is required by the SPL interface `IteratorAggregate`.
     * It will be implicitly called when you use `foreach` to traverse the collection.
     *
     * @param array $only
     * @param array $exclude
     * @return \ArrayIterator an iterator for traversing the tokens in the collection.
     */
    public function getIterator(array $only = [], array $exclude = [])
    {
        return new \ArrayIterator($this->getAll($only, $exclude));
    }

    /**
     * Returns the number of tokens in the collection.
     * This method is required by the SPL `Countable` interface.
     * It will be implicitly called when you use `count($collection)`.
     *
     * @return integer the number of tokens in the collection.
     */
    public function count()
    {
        return $this->getCount();
    }

    /**
     * Returns the number of tokens in the collection.
     *
     * @return integer the number of tokens in the collection.
     */
    public function getCount()
    {
        return count($this->getAll());
    }

    /**
     * Removes all data resource.
     */
    public function removeAll()
    {
        static::$storage->removeAllFlashes();
    }


    /**
     * @inheritdoc
     */
    public function getAll(array $only = [], array $exclude = [])
    {
        return ArrayHelper::prepareArray(static::$storage->getAllFlashes(), $only, $exclude);
    }

    /**
     * Returns whether there is a cookie with the specified name.
     * This method is required by the SPL interface `ArrayAccess`.
     * It is implicitly called when you use something like `isset($collection[$name])`.
     *
     * @param string $name the cookie name
     * @return boolean whether the named cookie exists
     */
    public function offsetExists($name)
    {
        return $this->has($name);
    }

    /**
     * Returns whether there is a cookie with the specified name.
     *
     * @param string $name the cookie name
     * @return boolean whether the named cookie exists
     */
    public function has($name)
    {
        return static::$storage->hasFlash($this->addPrefix($name), true);
    }

    /**
     * Returns the cookie with the specified name.
     * This method is required by the SPL interface `ArrayAccess`.
     * It is implicitly called when you use something like `$cookie = $collection[$name];`.
     * This is equivalent to [[get()]].
     *
     * @param string $name the cookie name
     * @return mixed the cookie with the specified name, null if the named cookie does not exist.
     */
    public function offsetGet($name)
    {
        return $this->get($name);
    }

    /**
     * Returns the cookie with the specified name.
     *
     * @param string $name the cookie name
     * @param bool $delete whether to delete this flash message right after this method is called.
     * @return mixed the cookie with the specified name. Null if the named cookie does not exist.
     */
    public function get($name, $delete = false)
    {
        return static::$storage->getFlash($this->addPrefix($name), null, $delete);
    }

    /**
     *
     * @param string $name the resource name
     * @param mixed  $value
     * @return string
     */
    public function offsetSet($name, $value = null)
    {
        return $this->create($name);
    }

    /**
     * Removes the named cookie.
     * This method is required by the SPL interface `ArrayAccess`.
     * It is implicitly called when you use something like `unset($collection[$name])`.
     * This is equivalent to [[remove()]].
     *
     * @param string $name the cookie name
     */
    public function offsetUnset($name)
    {
        $this->remove($name);
    }

    /**
     * Get data of resource
     *
     * @param string $name - key of array
     * @return string
     */
    public function __get($name)
    {
        return $this->get($name);
    }


    /**
     * @param string     $name
     * @param null $value
     * @return string
     */
    public function __set($name, $value = null)
    {
        return $this->create($name);
    }

    /**
     * @param array $names
     * @return mixed
     */
    public function getMulti(array $names)
    {
        $result = [];
        foreach ($names as $name) {
            $result[$name] = $this->get($name);
        }

        return $result;
    }

    /**
     * @param string $name
     * @param mixed  $value
     * @return string
     */
    public function add($name, $value = null)
    {
        return $this->create($name);
    }

    /**
     * @param array $names
     */
    public function addMulti(array $names)
    {
        foreach ($names as $name) {
            $this->create($name);
        }
    }

    /**
     * @param array $names
     */
    public function removeMulti(array $names)
    {
        foreach ($names as $name) {
            $this->remove($name);
        }
    }
}
