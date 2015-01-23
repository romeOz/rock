<?php
namespace rock\session;

use rock\base\Alias;
use rock\di\Container;
use rock\helpers\ArrayHelper;
use rock\request\Request;

/**
 * Session provides session data management and the related configurations.
 *
 * Session is a Web application component that can be accessed via {@see \rock\RockInterface::$session}.
 *
 * To start the session, call {@see \rock\session\DbSession::open()}; To complete and send out session data, call {@see \rock\session\Session::close()};
 * To destroy the session, call {@see \rock\session\DbSession::destroy()}.
 *
 * Session can be used like an array to set and get session data. For example,
 *
 * ```php
 * $session = new \rock\session\Session;
 * $session->open();
 * $value1 = $session['name1'];  // get session variable 'name1'
 * $value2 = $session['name2'];  // get session variable 'name2'
 * foreach ($session as $name => $value) // traverse all session variables
 * $session['name3'] = $value3;  // set session variable 'name3'
 * ```
 *
 * Session can be extended to support customized session storage.
 * To do so, override {@see \rock\session\Session::getUseCustomStorage()} so that it returns true, and
 * override these methods with the actual logic about using custom storage:
 * {@see \rock\session\Session::openSession()}, {@see \rock\session\Session::closeSession()}, {@see \rock\session\Session::readSession()}, {@see \rock\session\Session::writeSession()},
 * {@see \rock\session\Session::destroySession()} and {@see \rock\session\Session::gcSession()}.
 *
 * Session also supports a special type of session data, called *flash messages*.
 * A flash message is available only in the current request and the next request.
 * After that, it will be deleted automatically. Flash messages are particularly
 * useful for displaying confirmation messages. To use flash messages, simply
 * call methods such as {@see \rock\session\SessionFlash::setFlash()}, {@see \rock\session\SessionFlash::getFlash()}.
 *
 * @property array $allFlashes Flash messages (key => message). This property is read-only.
 * @property array $cookieParams The session cookie parameters. This property is read-only.
 * @property integer $count The number of session variables. This property is read-only.
 * @property string $flash The key identifying the flash message. Note that flash messages and normal session
 * variables share the same name space. If you have a normal session variable using the same name, its value will
 * be overwritten by this method. This property is write-only.
 * @property float $gCProbability The probability (percentage) that the GC (garbage collection) process is
 * started on every session initialization, defaults to 1 meaning 1% chance.
 * @property boolean $hasSessionId Whether the current request has sent the session ID.
 * @property string $id The current session ID.
 * @property boolean $isActive Whether the session has started. This property is read-only.
 * @property \ArrayIterator $iterator An iterator for traversing the session variables. This property is
 * read-only.
 * @property string $name The current session name.
 * @property string $savePath The current session save path, defaults to '/tmp'.
 * @property integer $timeout The number of seconds after which data will be seen as 'garbage' and cleaned up.
 * The default value is 1440 seconds (or the value of "session.gc_maxlifetime" set in php.ini).
 * @property boolean|null $useCookies The value indicating whether cookies should be used to store session
 * IDs.
 * @property boolean $useCustomStorage Whether to use custom storage. This property is read-only.
 * @property boolean $useTransparentSessionID Whether transparent sid support is enabled or not, defaults to
 * false.
 */
class Session extends SessionFlash implements \ArrayAccess, SessionInterface
{
    const NOT_USE_COOKIES = 1;
    const USE_ONLY_COOKIES = 2;
    const USE_COOKIES = 2;

    public $handler;

    /**
     * @var array parameter-value pairs to override default session cookie parameters that are used for session_set_cookie_params() function
     * Array may have the following possible keys: 'lifetime', 'path', 'domain', 'secure', 'httponly'
     * @see http://www.php.net/manual/en/function.session-set-cookie-params.php
     */
    public $cookieParams = ['httponly' => true];

    public function __construct($config = [])
    {
        $this->parentConstruct($config);
        $this->open();
        register_shutdown_function([$this, 'close']);
    }

    /**
     * Returns a value indicating whether to use custom session storage.
     * 
     * This method should be overridden to return true by child classes that implement custom session storage.
     * To implement custom session storage, override these methods: {@see \rock\session\Session::openSession()}, {@see \rock\session\Session::closeSession()},
     * {@see \rock\session\Session::readSession()}, {@see \rock\session\Session::writeSession()}, {@see \rock\session\Session::destroySession()} and {@see \rock\session\Session::gcSession()}.
     * @return boolean whether to use custom storage.
     */
    public function getUseCustomStorage()
    {
        return false;
    }

    /**
     * Start session
     */
    public function open()
    {
        if ($this->getIsActive()) {
            return;
        }
        $this->registerSessionHandler();
        $this->setCookieParamsInternal();
        @session_start();

        if ($this->getIsActive()) {
            $this->updateFlashCounters();
        } else {
            $error = error_get_last();
            $message = isset($error['message']) ? $error['message'] : 'Failed to start session.';
            throw new SessionException($message);
        }
    }

    /**
     * Registers session handler.
     *
     * @throws SessionException
     */
    protected function registerSessionHandler()
    {
        if ($this->handler !== null) {
            if (!is_object($this->handler)) {
                $this->handler = Container::load($this->handler);
            }
            if (!$this->handler instanceof \SessionHandlerInterface) {
                throw new SessionException('"' . get_class($this) . '::handler" must implement the SessionHandlerInterface.');
            }
            @session_set_save_handler($this->handler, false);
        } elseif ($this->getUseCustomStorage()) {
            @session_set_save_handler(
                [$this, 'openSession'],
                [$this, 'closeSession'],
                [$this, 'readSession'],
                [$this, 'writeSession'],
                [$this, 'destroySession'],
                [$this, 'gcSession']
            );
        }
    }

    /**
     * Ends the current session and store session data.
     */
    public function close()
    {
        if ($this->getIsActive()) {
            @session_write_close();
        }
    }

    /**
     * Frees all session variables and destroys all data registered to a session.
     */
    public function destroy()
    {
        if ($this->getIsActive()) {
            @session_unset();
            @session_destroy();
        }
    }

    /**
     * @return boolean whether the session has started
     */
    public function getIsActive()
    {
        return session_status() == PHP_SESSION_ACTIVE;
    }

    private $_hasSessionId;

    /**
     * Returns a value indicating whether the current request has sent the session ID.
     * 
     * The default implementation will check cookie and $_GET using the session name.
     * If you send session ID via other ways, you may need to override this method
     * or call {@see \rock\session\Session::setHasSessionId()} to explicitly set whether the session ID is sent.
     * @return boolean whether the current request has sent the session ID.
     */
    public function getHasSessionId()
    {
        if ($this->_hasSessionId === null) {
            $name = $this->getName();
            if (ini_get('session.use_cookies') && !empty($_COOKIE[$name])) {
                $this->_hasSessionId = true;
            } elseif (!ini_get('use_only_cookies') && ini_get('use_trans_sid')) {
                $this->_hasSessionId = Request::get($name) !== null;
            } else {
                $this->_hasSessionId = false;
            }
        }

        return $this->_hasSessionId;
    }

    /**
     * Sets the value indicating whether the current request has sent the session ID.
     * 
     * This method is provided so that you can override the default way of determining
     * whether the session ID is sent.
     * @param boolean $value whether the current request has sent the session ID.
     */
    public function setHasSessionId($value)
    {
        $this->_hasSessionId = $value;
    }

    /**
     * @return string the current session ID
     */
    public function getId()
    {
        return session_id();
    }

    /**
     * @param string $value the session ID for the current session
     */
    public function setId($value)
    {
        session_id($value);
    }

    /**
     * Updates the current session ID with a newly generated one .
     * 
     * Please refer to <http://php.net/session_regenerate_id> for more details.
     * @param boolean $deleteOldSession Whether to delete the old associated session file or not.
     */
    public function regenerateID($deleteOldSession = false)
    {
        @session_regenerate_id($deleteOldSession);
    }

    /**
     * @return string the current session name
     */
    public function getName()
    {
        return session_name();
    }

    /**
     * @param string $value the session name for the current session, must be an alphanumeric string.
     * It defaults to "PHPSESSID".
     */
    public function setName($value)
    {
        session_name($value);
    }

    /**
     * @return string the current session save path, defaults to '/tmp'.
     */
    public function getSavePath()
    {
        return session_save_path();
    }

    /**
     * @param string $value the current session save path. This can be either a directory name or a path alias.
     * @throws SessionException if the path is not a valid directory
     */
    public function setSavePath($value)
    {
        $path = Alias::getAlias($value);
        if (is_dir($path)) {
            session_save_path($path);
        } else {
            throw new SessionException("Session save path is not a valid directory: $value");
        }
    }

    /**
     * @return array the session cookie parameters.
     * @see http://us2.php.net/manual/en/function.session-get-cookie-params.php
     */
    public function getCookieParams()
    {
        return array_merge(session_get_cookie_params(), array_change_key_case($this->cookieParams));
    }

    /**
     * Sets the session cookie parameters.
     * 
     * The cookie parameters passed to this method will be merged with the result
     * of `session_get_cookie_params()`.
     * @param array $value cookie parameters, valid keys include: `lifetime`, `path`, `domain`, `secure` and `httponly`.
     * @see http://us2.php.net/manual/en/function.session-set-cookie-params.php
     */
    public function setCookieParams(array $value)
    {
        $this->cookieParams = $value;
    }

    /**
     * Sets the session cookie parameters.
     * 
     * This method is called by {@see \rock\session\DbSession::open()} when it is about to open the session.
     *
     * @throws SessionException if the parameters are incomplete.
     * @see http://us2.php.net/manual/en/function.session-set-cookie-params.php
     */
    private function setCookieParamsInternal()
    {
        $data = $this->getCookieParams();
        if (isset($data['lifetime'], $data['path'], $data['domain'], $data['secure'], $data['httponly'])) {
            session_set_cookie_params($data['lifetime'], $data['path'], $data['domain'], $data['secure'], $data['httponly']);
        } else {
            throw new SessionException('Please make sure cookieParams contains these elements: lifetime, path, domain, secure and httponly.');
        }

        if (isset($data['setUseCookies'])) {
            $this->setUseCookies($data['setUseCookies']);
        }
    }

    /**
     * Returns the value indicating whether cookies should be used to store session IDs.
     * 
     * @return boolean|null the value indicating whether cookies should be used to store session IDs.
     * @see setUseCookies()
     */
    public function getUseCookies()
    {
        if (ini_get('session.use_cookies') === '0') {
            return false;
        } elseif (ini_get('session.use_only_cookies') === '1') {
            return true;
        } else {
            return null;
        }
    }

    /**
     * Sets the value indicating whether cookies should be used to store session IDs.
     * 
     * Three states are possible:
     *
     * - {@see \rock\session\Session::USE_ONLY_COOKIES}: cookies and only cookies will be used to store session IDs.
     * - {@see \rock\session\Session::NOT_USE_COOKIES}: cookies will not be used to store session IDs.
     * - {@see \rock\session\Session::USE_COOKIES}: if possible, cookies will be used to store session IDs; if not, other mechanisms will be used (e.g. GET parameter)
     *
     * @param int $value the value indicating whether cookies should be used to store session IDs.
     */
    public function setUseCookies($value =  self::USE_ONLY_COOKIES)
    {
        if ($value === self::NOT_USE_COOKIES) {
            ini_set('session.use_cookies', '0');
            ini_set('session.use_only_cookies', '0');
        } elseif ($value === self::USE_ONLY_COOKIES) {
            ini_set('session.use_cookies', '1');
            ini_set('session.use_only_cookies', '1');
        } else {
            ini_set('session.use_cookies', '1');
            ini_set('session.use_only_cookies', '0');
        }
    }

    /**
     * @return float the probability (percentage) that the GC (garbage collection) process is started on every session initialization, defaults to 1 meaning 1% chance.
     */
    public function getGCProbability()
    {
        return (float) (ini_get('session.gc_probability') / ini_get('session.gc_divisor') * 100);
    }

    /**
     * @param float $value the probability (percentage) that the GC (garbage collection) process is started on every session initialization.
     * @throws SessionException if the value is not between 0 and 100.
     */
    public function setGCProbability($value)
    {
        if ($value >= 0 && $value <= 100) {
            // percent * 21474837 / 2147483647 ≈ percent * 0.01
            ini_set('session.gc_probability', floor($value * 21474836.47));
            ini_set('session.gc_divisor', 2147483647);
        } else {
            throw new SessionException('GCProbability must be a value between 0 and 100.');
        }
    }

    /**
     * @return boolean whether transparent sid support is enabled or not, defaults to false.
     */
    public function getUseTransparentSessionID()
    {
        return ini_get('session.use_trans_sid') == 1;
    }

    /**
     * @param boolean $value whether transparent sid support is enabled or not.
     */
    public function setUseTransparentSessionID($value)
    {
        ini_set('session.use_trans_sid', $value ? '1' : '0');
    }

    /**
     * @return integer the number of seconds after which data will be seen as 'garbage' and cleaned up.
     * The default value is 1440 seconds (or the value of "session.gc_maxlifetime" set in php.ini).
     */
    public function getTimeout()
    {
        return (int) ini_get('session.gc_maxlifetime');
    }

    /**
     * @param integer $value the number of seconds after which data will be seen as 'garbage' and cleaned up
     */
    public function setTimeout($value)
    {
        ini_set('session.gc_maxlifetime', $value);
    }

    /**
     * Session open handler.
     * 
     * This method should be overridden if {@see \rock\session\Session::getUseCustomStorage()} returns true.
     * Do not call this method directly.
     * @param string $savePath session save path
     * @param string $sessionName session name
     * @return boolean whether session is opened successfully
     */
    public function openSession($savePath, $sessionName)
    {
        return true;
    }

    /**
     * Session close handler.
     * 
     * This method should be overridden if {@see \rock\session\Session::getUseCustomStorage()} returns true.
     * Do not call this method directly.
     * @return boolean whether session is closed successfully
     */
    public function closeSession()
    {
        return true;
    }

    /**
     * Session read handler.
     * 
     * This method should be overridden if {@see \rock\session\Session::getUseCustomStorage()} returns true.
     * Do not call this method directly.
     * @param string $id session ID
     * @return string the session data
     */
    public function readSession($id)
    {
        return '';
    }

    /**
     * Session write handler.
     * 
     * This method should be overridden if {@see \rock\session\Session::getUseCustomStorage()} returns true.
     * Do not call this method directly.
     * @param string $id session ID
     * @param string $data session data
     * @return boolean whether session write is successful
     */
    public function writeSession($id, $data)
    {
        return true;
    }

    /**
     * Session destroy handler.
     * 
     * This method should be overridden if {@see \rock\session\Session::getUseCustomStorage()} returns true.
     * Do not call this method directly.
     * @param string $id session ID
     * @return boolean whether session is destroyed successfully
     */
    public function destroySession($id)
    {
        return true;
    }

    /**
     * Session GC (garbage collection) handler.
     * 
     * This method should be overridden if {@see \rock\session\Session::getUseCustomStorage()} returns true.
     * Do not call this method directly.
     * @param integer $maxLifetime the number of seconds after which data will be seen as 'garbage' and cleaned up.
     * @return boolean whether session is GCed successfully
     */
    public function gcSession($maxLifetime)
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function get($keys, $default = null)
    {
        $array = $_SESSION;
        return ArrayHelper::getValue($array, $keys, $default);
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
    public function offsetGet($keys)
    {
        return $this->get($keys);
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
     * Returns an iterator for traversing the session variables.
     * 
     * This method is required by the interface IteratorAggregate.
     *
     * @param array $only
     * @param array $exclude
     * @return \ArrayIterator an iterator for traversing the session variables.
     */
    public function getIterator(array $only = [], array $exclude = [])
    {
        return new \ArrayIterator($this->getAll($only, $exclude));
    }

    /**
     * @inheritdoc
     */
    public function getAll(array $only = [], array $exclude = [])
    {
        return ArrayHelper::only($_SESSION, $only, $exclude);
    }

    /**
     * @inheritdoc
     */
    public function add($keys, $value)
    {
        if (!isset($_SESSION)) {
            $_SESSION = [];
        }
        $array = $_SESSION;
        $_SESSION = ArrayHelper::setValue($array, $keys, $value);
    }

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
    public function addMulti(array $data)
    {
        $_SESSION = array_merge($_SESSION, $data);
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
    public function getCount()
    {
        return count($_SESSION);
    }

    /**
     * @inheritdoc
     */
    public function remove($keys)
    {
        $_SESSION = ArrayHelper::removeValue($_SESSION, $keys);
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
    public function removeMulti(array $keys)
    {
        foreach ($keys as $key) {
            $this->remove($key);
        }
    }

    /**
     * @inheritdoc
     */
    public function removeAll()
    {
        $_SESSION = [];
    }
}