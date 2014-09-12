<?php
namespace rock\token;


use rock\base\ComponentsInterface;
use rock\base\ComponentsTrait;
use rock\base\ObjectTrait;
use rock\base\StorageInterface;
use rock\cookie\Cookie;
use rock\request\RequestInterface;
use rock\session\SessionInterface;

class Token implements ComponentsInterface, RequestInterface, StorageInterface
{
    use ComponentsTrait;

    /**
     * The name of the HTTP header for sending CSRF token.
     */
    const CSRF_HEADER = 'X-CSRF-Token';
    /**
     * The adapter where to store the token: cookies or session (by default).
     * @var int
     * @see StorageInterface
     */
    public $adapterStorage = self::SESSION;
    /**
     * Creating multiple tokens.
     * @var bool
     */
    public $multiToken = false;
    /**
     * @var boolean whether to enable CSRF (Cross-Site Request Forgery) validation. Defaults to true.
     * When CSRF validation is enabled, forms submitted to an Rock Web application must be originated
     * from the same application. If not, a 400 HTTP exception will be raised.
     *
     * Note, this feature requires that the user client accepts cookie. Also, to use this feature,
     * forms submitted via POST method must contain a hidden input whose name is specified by [[csrfVar]].
     * You may use `\rock\helpers\Html::beginForm()` to generate his hidden input.
     *
     * @see http://en.wikipedia.org/wiki/Cross-site_request_forgery
     */
    public $enableCsrfValidation = true;
    /**
     * @var string the name of the token used to prevent CSRF. Defaults to '_csrf'.
     * This property is used only when [[enableCsrfValidation]] is true.
     */
    public $csrfPrefix = '_csrf';
    /** @var  SessionInterface */
    protected static $storage;
    /** @var  string */
    private static $_token;

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
     * Creating csrf-token
     *
     * @param string $name - name of token
     * @return string
     */
    public function create($name = '')
    {
        if ($this->enableCsrfValidation === false) {
            return null;
        }

        if ($this->multiToken === false) {
            $name = '';
            if (isset(self::$_token)) {
                return self::$_token;
            }
        }
        $name = $this->addPrefix($name);
        $token = self::$_token = $this->Rock->security->generateRandomKey();
        static::$storage->add($name, $token);
        return $token;
    }

    /**
     * Adding prefix
     *
     * @param string $name
     * @return string
     */
    public function addPrefix($name = '')
    {
        if (isset($this->csrfPrefix)) {
            return $this->csrfPrefix . $name;
        }

        return $name;
    }

    /**
     * Validation token.
     *
     * @param string $token - value of token.
     * @param string $name  - name of token.
     * @return bool
     */
    public function valid($token = null, $name = '')
    {
        if ($this->enableCsrfValidation === false) {
            return true;
        }
        if (!empty($token)) {
            if ($this->getCsrfTokenFromHeader() === $this->get($name) || $this->get($name) === $token) {
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
        static::$storage->remove($this->addPrefix($name));
    }

    /**
     * Returns whether there is a cookie with the specified name.
     *
     * @param string $name the cookie name
     * @return boolean whether the named cookie exists
     */
    public function has($name)
    {
        if ($this->multiToken === false) {
            $name = '';
        }
        return static::$storage->has($this->addPrefix($name));
    }

    /**
     * Returns the cookie with the specified name.
     *
     * @param string $name the cookie name
     * @return mixed the cookie with the specified name. Null if the named cookie does not exist.
     */
    public function get($name = '')
    {
        if ($this->multiToken === false) {
            $name = '';
        }
        return static::$storage->get($this->addPrefix($name));
    }
}
