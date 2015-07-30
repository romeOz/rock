<?php
namespace rock\user;

use rock\base\BaseException;
use rock\base\CollectionInterface;
use rock\base\ObjectInterface;
use rock\cookie\Cookie;
use rock\events\EventsTrait;
use rock\helpers\ArrayHelper;
use rock\helpers\Instance;
use rock\log\Log;
use rock\request\Request;
use rock\Rock;
use rock\session\Session;
use rock\url\Url;

/**
 * User is the class for the "user" application component that manages the user authentication status.
 * @property string $returnUrl
 * @package rock\user
 */
class User implements \ArrayAccess, CollectionInterface, ObjectInterface
{
    use EventsTrait;

    const EVENT_BEFORE_LOGIN = 'beforeLogin';
    const EVENT_AFTER_LOGIN = 'afterLogin';
    const EVENT_BEFORE_LOGOUT = 'beforeLogout';
    const EVENT_AFTER_LOGOUT = 'afterLogout';

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
    /**
     * @var boolean whether to use session to persist authentication status across multiple requests.
     * You set this property to be false if your application is stateless, which is often the case
     * for RESTful APIs.
     */
    public $enableSession = true;

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
        if (!$this->enableSession) {
            return false;
        }
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
        if (!$this->enableSession || !isset($value)) {
            return;
        }
        $this->storage->add($this->prepareKeys($keys), $value);
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

        $ip = $this->request->getUserIP();
        $id = $this->get('id');
        if ($this->enableSession) {
            $msg = "User '$id' logged in from {$ip}.";
        } else {
            $msg = "User '$id' logged in from $ip. Session not enabled.";
        }
        Log::info(BaseException::convertExceptionToString(new BaseException($msg)));
    }

    /**
     * Logout user.
     * @param bool $destroy destroy session.
     */
    public function logout($destroy = true)
    {
        if (!$this->enableSession) {
            return;
        }
        $ip = $this->request->getUserIP();
        $id = $this->get('id');
        Log::info(BaseException::convertExceptionToString(new BaseException("User '$id' logged out from $ip.")));
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
     * @param bool $allowCaching
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
        return static::$access[$roleName] = Rock::$app->rbac->check($this->get('id'), $roleName, $params);
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

        return $url === null ? $this->request->getHomeUrl() : Url::modify($url);
    }

    /**
     * Remembers the URL in the session so that it can be retrieved back later by {@see \rock\user\User::getReturnUrl()}.
     * @param string|array $url the URL that the user should be redirected to after login.
     */
    public function setReturnUrl($url)
    {
        $this->storage->add($this->returnUrlParam, $url);
    }

    /**
     * This method is called before logging in a user.
     * The default implementation will trigger the {@see \rock\user\User::EVENT_BEFORE_LOGIN} event.
     * If you override this method, make sure you call the parent implementation
     * so that the event is triggered.
     * If 0, it means login till the user closes the browser or the session is manually destroyed.
     * @return boolean whether the user should continue to be logged in
     */
    protected function beforeLogin()
    {
        $event = new UserEvent();
        $this->trigger(self::EVENT_BEFORE_LOGIN, $event);
        return $event->isValid;
    }

    /**
     * This method is called after the user is successfully logged in.
     * The default implementation will trigger the {@see \rock\user\User::EVENT_AFTER_LOGIN} event.
     * If you override this method, make sure you call the parent implementation
     * so that the event is triggered.
     * If 0, it means login till the user closes the browser or the session is manually destroyed.
     */
    protected function afterLogin()
    {
        $this->trigger(self::EVENT_AFTER_LOGIN, new UserEvent());
    }

    /**
     * This method is invoked when calling {@see \rock\user\User::logout()} to log out a user.
     * The default implementation will trigger the {@see \rock\user\User::EVENT_BEFORE_LOGOUT} event.
     * If you override this method, make sure you call the parent implementation
     * so that the event is triggered.
     * @return boolean whether the user should continue to be logged out
     */
    protected function beforeLogout()
    {
        $event = new UserEvent();
        $this->trigger(self::EVENT_BEFORE_LOGOUT, $event);
        return $event->isValid;
    }

    /**
     * This method is invoked right after a user is logged out via {@see \rock\user\User::logout()}.
     * The default implementation will trigger the {@see \rock\user\User::EVENT_AFTER_LOGOUT} event.
     * If you override this method, make sure you call the parent implementation
     * so that the event is triggered.
     */
    protected function afterLogout()
    {
        $this->trigger(self::EVENT_AFTER_LOGOUT, new UserEvent());
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