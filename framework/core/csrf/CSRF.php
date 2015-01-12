<?php
namespace rock\csrf;


use rock\base\ComponentsInterface;
use rock\base\ComponentsTrait;
use rock\cookie\Cookie;
use rock\di\Container;
use rock\request\RequestInterface;
use rock\session\SessionInterface;

class CSRF implements ComponentsInterface, RequestInterface
{
    use ComponentsTrait;

    /**
     * The name of the HTTP header for sending CSRF-token.
     */
    const CSRF_HEADER = 'X-CSRF-Token';
    /**
     * @event
     */
    const EVENT_AFTER_VALID = 'afterValid';
    /**
     * @var boolean whether to enable CSRF (Cross-Site Request Forgery) validation. Defaults to true.
     * When CSRF validation is enabled, forms submitted to an Rock Web application must be originated
     * from the same application. If not, a 400 HTTP exception will be raised.
     *
     * Note, this feature requires that the user client accepts cookie. Also, to use this feature,
     * forms submitted via POST method must contain a hidden input whose name is specified by @see \rock\csrf\CSRF::csrfParam.
     * You may use {@see \rock\helpers\Html::beginForm()} to generate his hidden input.
     *
     * @link http://en.wikipedia.org/wiki/Cross-site_request_forgery
     */
    public $enableCsrfValidation = true;
    /**
     * @var string the name of the token used to prevent CSRF. Defaults to `_csrf`.
     * This property is used only when @see \rock\csrf\CSRF::enableCsrfValidation is true.
     */
    public $csrfParam = '_csrf';
    /**
     * The adapter where to store the token: cookies or session (by default).
     * @var string|array|SessionInterface
     */
    public $storage = 'session';


    public function init()
    {
        if (!is_object($this->storage)) {
            $this->storage = Container::load($this->storage);
        }
        
        if ($this->storage instanceof Cookie) {
            $this->storage->httpOnly = true;
        }
    }

    /**
     * Creating CSRF-token
     *
     * @param boolean $regenerate whether to regenerate CSRF token. When this parameter is true, each time
     * this method is called, a new CSRF token will be generated and persisted (in session or cookie).
     * @return string
     */
    public function get($regenerate = false)
    {
        if ($this->enableCsrfValidation === false) {
            return null;
        }
        if ($regenerate || ($token = $this->load()) === null) {
            $token = $this->generate();
        }

        return $token;
    }

    protected function generate()
    {
        $token = $this->Rock->security->generateRandomKey();
        $this->storage->add($this->csrfParam, $token);
        return $token;
    }

    /**
     * Validation token.
     *
     * @param string $compare value of token.
     * @return bool
     */
    public function valid($compare = null)
    {
        if ($this->enableCsrfValidation === false) {
            return true;
        }
        if (!empty($compare)) {
            $token = $this->load();
            if ($this->getCsrfTokenFromHeader() === $token || $token === $compare) {
                $this->trigger(self::EVENT_AFTER_VALID);
                return true;
            }
        }
        $this->trigger(self::EVENT_AFTER_VALID);
        return false;
    }

    /**
     * @return string the CSRF token
     * sent via {@see \rock\csrf\CSRF::CSRF_HEADER} by browser. Null is returned if no such header is sent.
     */
    public function getCsrfTokenFromHeader()
    {
        $key = 'HTTP_' . str_replace('-', '_', strtoupper(self::CSRF_HEADER));

        return isset($_SERVER[$key]) ? $_SERVER[$key] : null;
    }

    /**
     * Removes CSRF-token.
     */
    public function remove()
    {
        $this->storage->remove($this->csrfParam);
    }

    /**
     * Exists CSRF-token.
     *
     * @return boolean
     */
    public function exists()
    {
        return $this->storage->exists($this->csrfParam);
    }

    /**
     * Returns the cookie with the specified name.
     *
     * @return string
     */
    protected function load()
    {
        return $this->storage->get($this->csrfParam);
    }
}