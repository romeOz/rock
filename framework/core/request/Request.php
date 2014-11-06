<?php
namespace rock\request;

use rock\base\ComponentsInterface;
use rock\base\ComponentsTrait;
use rock\helpers\Helper;
use rock\helpers\Json;
use rock\Rock;
use rock\sanitize\Sanitize;

/**
 * Class `Request`
 *
 * @property-read string scheme
 * @property-read string host
 * @property-read string hostInfo
 * @property-read string queryString
 * @property-read array $eTags The entity tags. This property is read-only.
 *
 * @package rock\request
 */
class Request implements RequestInterface, ComponentsInterface
{
    use ComponentsTrait {
        ComponentsTrait::__construct as parentConstruct;
        ComponentsTrait::__get as parentGet;
    }

    /**
     * Checking referrer on allow domains
     * @var array
     */
    public $allowDomains = [];
    /**
     * @var string|boolean the name of the POST parameter that is used to indicate if a request is a PUT, PATCH or DELETE
     * request tunneled through POST. Default to '_method'.
     * @see getMethod()
     * @see getBodyParams()
     */
    public $methodVar = '_method';
    /**
     * @var boolean whether to show entry script name in the constructed URL. Defaults to true.
     */
    public $showScriptName = true;

    public function __construct($config = [])
    {
        $this->parentConstruct($config);
        $this->isSelfDomain(true);

        $this->parseRequest();
    }

    /**
     * @param Sanitize $sanitize
     * @return mixed
     */
    public static function getAll(Sanitize $sanitize = null)
    {
        return static::prepareAll($GLOBALS['_GET'], $sanitize);
    }

    /**
     * @param Sanitize $sanitize
     * @return mixed
     */
    public static function postAll(Sanitize $sanitize = null)
    {
        return static::prepareAll($GLOBALS['_POST'], $sanitize);
    }

    /**
     * @param Sanitize $sanitize
     * @return mixed
     */
    public static function putAll(Sanitize $sanitize = null)
    {
        return static::prepareAll($GLOBALS['_PUT'], $sanitize);
    }

    /**
     * @param Sanitize $sanitize
     * @return mixed
     */
    public static function deleteAll(Sanitize $sanitize = null)
    {
        return static::prepareAll($GLOBALS['_DELETE'], $sanitize);
    }

    /**
     * @param Sanitize $sanitize
     * @return mixed
     */
    public static function serverAll(Sanitize $sanitize = null)
    {
        return static::prepareAll($GLOBALS['_SERVER'], $sanitize);
    }

    /**
     * @param string      $name - name of request-value
     * @param mixed  $default
     * @param Sanitize $sanitize
     * @return mixed
     */
    public static function get($name, $default = null, Sanitize $sanitize = null)
    {
        return static::prepareValue('_GET', $name, $default, $sanitize);
    }

    /**
     * @param string      $name - name of request-value
     * @param mixed  $default
     * @param Sanitize $sanitize
     * @return mixed
     */
    public static function post($name, $default = null, Sanitize $sanitize = null)
    {
        return static::prepareValue('_POST', $name, $default, $sanitize);
    }

    /**
     * @param string      $name - name of request-value
     * @param mixed  $default
     * @param Sanitize $sanitize
     * @return mixed
     */
    public static function put($name, $default = null, Sanitize $sanitize = null)
    {
        return static::prepareValue('_PUT', $name, $default, $sanitize);
    }

    /**
     * @param string      $name - name of request-value
     * @param mixed  $default
     * @param Sanitize $sanitize
     * @return mixed
     */
    public static function delete($name, $default = null, Sanitize $sanitize = null)
    {
        return self::prepareValue('_DELETE', $name, $default, $sanitize);
    }

    private static $_contentTypes;
    
    /**
     * Returns the content types acceptable by the end user.
     * This is determined by the `Accept` HTTP header. For example,
     *
     * ```php
     * $_SERVER['HTTP_ACCEPT'] = 'text/plain; q=0.5, application/json; version=1.0, application/xml; version=2.0;';
     * $types = $request->getAcceptableContentTypes();
     * print_r($types);
     * // displays:
     * // [
     * //     'application/json' => ['q' => 1, 'version' => '1.0'],
     * //      'application/xml' => ['q' => 1, 'version' => '2.0'],
     * //           'text/plain' => ['q' => 0.5],
     * // ]
     * ```
     *
     * @return array the content types ordered by the quality score. Types with the highest scores
     * will be returned first. The array keys are the content types, while the array values
     * are the corresponding quality score and other parameters as given in the header.
     */
    public function getAcceptableContentTypes()
    {
        if (self::$_contentTypes === null) {
            if (isset($_SERVER['HTTP_ACCEPT'])) {
                self::$_contentTypes = $this->parseAcceptHeader($_SERVER['HTTP_ACCEPT']);
            } else {
                self::$_contentTypes = [];
            }
        }

        return self::$_contentTypes;
    }

    /**
     * Sets the acceptable content types.
     * Please refer to @see getAcceptableContentTypes() on the format of the parameter.
     * @param array $value the content types that are acceptable by the end user. They should
     * be ordered by the preference level.
     * @see getAcceptableContentTypes()
     * @see parseAcceptHeader()
     */
    public function setAcceptableContentTypes($value)
    {
        self::$_contentTypes = $value;
    }
    
    /**
     * Returns request content-type
     * The Content-Type header field indicates the MIME type of the data
     * contained in the case of the HEAD method, the
     * media type that would have been sent had the request been a GET.
     * For the MIME-types the user expects in response, see @see getAcceptableContentTypes() .
     * @return string request content-type. Null is returned if this information is not available.
     * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.17
     * HTTP 1.1 header field definitions
     */
    public function getContentType()
    {
        if (isset($_SERVER["CONTENT_TYPE"])) {
            return $_SERVER["CONTENT_TYPE"];
        } elseif (isset($_SERVER["HTTP_CONTENT_TYPE"])) {
            //fix bug https://bugs.php.net/bug.php?id=66606
            return $_SERVER["HTTP_CONTENT_TYPE"];
        }

        return null;
    }
    
    private static $_languages;


    /**
     * Returns the languages acceptable by the end user.
     * This is determined by the `Accept-Language` HTTP header.
     * @return array the languages ordered by the preference level. The first element
     * represents the most preferred language.
     */
    public static function getAcceptableLanguages()
    {
        if (self::$_languages === null) {
            if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
                self::$_languages = static::parseAcceptHeader($_SERVER['HTTP_ACCEPT_LANGUAGE']);
            } else {
                self::$_languages = [];
            }
        }
        return self::$_languages;
    }

    /**
     * Parses the given `Accept` (or `Accept-Language`) header.
     *
     * This method will return the acceptable values with their quality scores and the corresponding parameters
     * as specified in the given `Accept` header. The array keys of the return value are the acceptable values,
     * while the array values consisting of the corresponding quality scores and parameters. The acceptable
     * values with the highest quality scores will be returned first. For example,
     *
     * ```php
     * $header = 'text/plain; q=0.5, application/json; version=1.0, application/xml; version=2.0;';
     * $accepts = $request->parseAcceptHeader($header);
     * print_r($accepts);
     * // displays:
     * // [
     * //     'application/json' => ['q' => 1, 'version' => '1.0'],
     * //      'application/xml' => ['q' => 1, 'version' => '2.0'],
     * //           'text/plain' => ['q' => 0.5],
     * // ]
     * ```
     *
     * @param string $header the header to be parsed
     * @return array the acceptable values ordered by their quality score. The values with the highest scores
     * will be returned first.
     */
    public static function parseAcceptHeader($header)
    {
        $accepts = [];
        foreach (explode(',', $header) as $i => $part) {
            $params = preg_split('/\s*;\s*/', trim($part), -1, PREG_SPLIT_NO_EMPTY);
            if (empty($params)) {
                continue;
            }
            $values = [
                'q' => [$i, array_shift($params), 1],
            ];
            foreach ($params as $param) {
                if (strpos($param, '=') !== false) {
                    list ($key, $value) = explode('=', $param, 2);
                    if ($key === 'q') {
                        $values['q'][2] = (double) $value;
                    } else {
                        $values[$key] = $value;
                    }
                } else {
                    $values[] = $param;
                }
            }
            $accepts[] = $values;
        }

        usort($accepts, function ($a, $b) {
                $a = $a['q']; // index, name, q
                $b = $b['q'];
                if ($a[2] > $b[2]) {
                    return -1;
                } elseif ($a[2] < $b[2]) {
                    return 1;
                } elseif ($a[1] === $b[1]) {
                    return $a[0] > $b[0] ? 1 : -1;
                } elseif ($a[1] === '*/*') {
                    return 1;
                } elseif ($b[1] === '*/*') {
                    return -1;
                } else {
                    $wa = $a[1][strlen($a[1]) - 1] === '*';
                    $wb = $b[1][strlen($b[1]) - 1] === '*';
                    if ($wa xor $wb) {
                        return $wa ? 1 : -1;
                    } else {
                        return $a[0] > $b[0] ? 1 : -1;
                    }
                }
            });

        $result = [];
        foreach ($accepts as $accept) {
            $name = $accept['q'][1];
            $accept['q'] = $accept['q'][2];
            $result[$name] = $accept;
        }

        return $result;
    }


    /**
     * @param array $value the languages that are acceptable by the end user. They should
     * be ordered by the preference level.
     */
    public static function setAcceptableLanguages($value)
    {
        self::$_languages = $value;
    }

    /**
     * Returns the user-preferred language that should be used by this application.
     * The language resolution is based on the user preferred languages and the languages
     * supported by the application. The method will try to find the best match.
     * @param array $languages a list of the languages supported by the application. If this is empty, the current
     * application language will be returned without further processing.
     * @return string the language that the application should use.
     */
    public static function getPreferredLanguage(array $languages = [])
    {
        if (empty($languages)) {
            return Rock::$app->language;
        }

        foreach (static::getAcceptableLanguages() as $acceptableLanguage => $q) {
            $acceptableLanguage = str_replace('_', '-', strtolower($acceptableLanguage));
            foreach ($languages as $language) {
                $language = str_replace('_', '-', strtolower($language));
                // en-us==en-us, en==en-us, en-us==en
                if ($language === $acceptableLanguage || strpos($acceptableLanguage, $language . '-') === 0 || strpos($language, $acceptableLanguage . '-') === 0) {
                    return $language;
                }
            }
        }

        return reset($languages);
    }

    /**
     * Gets the Etags.
     *
     * @return array The entity tags
     */
    public function getETags()
    {
        if (isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
            return preg_split('/[\s,]+/', $_SERVER['HTTP_IF_NONE_MATCH'], -1, PREG_SPLIT_NO_EMPTY);
        } else {
            return [];
        }
    }


    private $_pathInfo;

    /**
     * Returns the path info of the currently requested URL.
     * A path info refers to the part that is after the entry script and before the question mark (query string).
     * The starting and ending slashes are both removed.
     *
*@return string part of the request URL that is after the entry script and before the question mark.
     * Note, the returned path info is already URL-decoded.
     * @throws RequestException if the path info cannot be determined due to unexpected server configuration
     */
    public function getPathInfo()
    {
        if ($this->_pathInfo === null) {
            $this->_pathInfo = $this->resolvePathInfo();
        }

        return $this->_pathInfo;
    }

    /**
     * Sets the path info of the current request.
     * This method is mainly provided for testing purpose.
     * @param string $value the path info of the current request
     */
    public function setPathInfo($value)
    {
        $this->_pathInfo = ltrim($value, '/');
    }

    /**
     * Resolves the path info part of the currently requested URL.
     * A path info refers to the part that is after the entry script and before the question mark (query string).
     * The starting slashes are both removed (ending slashes will be kept).
     *
*@return string part of the request URL that is after the entry script and before the question mark.
     * Note, the returned path info is decoded.
     * @throws RequestException if the path info cannot be determined due to unexpected server configuration
     */
    protected function resolvePathInfo()
    {
        $pathInfo = $this->getUrl();

        if (($pos = strpos($pathInfo, '?')) !== false) {
            $pathInfo = substr($pathInfo, 0, $pos);
        }

        $pathInfo = urldecode($pathInfo);

        // try to encode in UTF8 if not so
        // http://w3.org/International/questions/qa-forms-utf-8.html
        if (!preg_match('%^(?:
            [\x09\x0A\x0D\x20-\x7E]              # ASCII
            | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
            | \xE0[\xA0-\xBF][\x80-\xBF]         # excluding overlongs
            | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
            | \xED[\x80-\x9F][\x80-\xBF]         # excluding surrogates
            | \xF0[\x90-\xBF][\x80-\xBF]{2}      # planes 1-3
            | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
            | \xF4[\x80-\x8F][\x80-\xBF]{2}      # plane 16
            )*$%xs', $pathInfo)
        ) {
            $pathInfo = utf8_encode($pathInfo);
        }

        $scriptUrl = $this->getScriptUrl();
        $baseUrl = $this->getBaseUrl();
        if (strpos($pathInfo, $scriptUrl) === 0) {
            $pathInfo = substr($pathInfo, strlen($scriptUrl));
        } elseif ($baseUrl === '' || strpos($pathInfo, $baseUrl) === 0) {
            $pathInfo = substr($pathInfo, strlen($baseUrl));
        } elseif (isset($_SERVER['PHP_SELF']) && strpos($_SERVER['PHP_SELF'], $scriptUrl) === 0) {
            $pathInfo = substr($_SERVER['PHP_SELF'], strlen($scriptUrl));
        } else {
            throw new RequestException('Unable to determine the path info of the current request.');
        }

        if ($pathInfo[0] === '/') {
            $pathInfo = substr($pathInfo, 1);
        }

        return (string) $pathInfo;
    }

    /**
     * Is self domain
     *
     * @param bool $throw - throw an exception (default: false)
     * @throws RequestException
     * @return bool
     */
    public function isSelfDomain($throw = false)
    {
        if (!$domains = $this->allowDomains) {
            return true;
        }

        if (!in_array(strtolower($_SERVER['SERVER_NAME']), $domains, true) ||
            !in_array(strtolower($_SERVER['HTTP_HOST']), $domains, true)
        ) {
            if ($throw === true) {
                throw new RequestException("Invalid domain: {$_SERVER['HTTP_HOST']}");
            } else {
                Rock::error("Invalid domain: {$_SERVER['HTTP_HOST']}");
            }

            return false;
        }

        return true;
    }


    /**
     * Returns the currently requested absolute URL.
     * This is a shortcut to the concatenation of @see getHostInfo()
     * and @see getUrl() .
     *
     * @param bool $strip
     * @return string the currently requested absolute URL.
     */
    public function getAbsoluteUrl($strip = true)
    {
        $url = $this->getHostInfo() . $this->getUrl();
        return $strip === true ? strip_tags($url) : $url;
    }

    private $_url;

    /**
     * Returns the currently requested relative URL.
     * This refers to the portion of the URL that is after the @see getHostInfo() part.
     * It includes the @see getQueryString() part if any.
     *
*@return string the currently requested relative URL. Note that the URI returned is URL-encoded.
     * @throws RequestException if the URL cannot be determined due to unusual server configuration
     */
    public function getUrl()
    {
        if ($this->_url === null) {
            $this->_url = $this->resolveRequestUri();
        }
        return $this->_url;
    }

    /**
     * Sets the currently requested relative URL.
     * The URI must refer to the portion that is after @see getHostInfo() .
     * Note that the URI should be URL-encoded.
     * @param string $value the request URI to be set
     */
    public function setUrl($value)
    {
        $this->_url = $value;
    }

    /**
     * Resolves the request URI portion for the currently requested URL.
     * This refers to the portion that is after the @see hostInfo part. It includes
     * the @see queryString part if any.
     * The implementation of this method referenced Zend_Controller_Request_Http in Zend Framework.
     *
     * @return string|boolean the request URI portion for the currently requested URL.
     * Note that the URI returned is URL-encoded.
     * @throws RequestException if the request URI cannot be determined due to unusual server configuration
     */
    protected function resolveRequestUri()
    {
        if (isset($_SERVER['HTTP_X_REWRITE_URL'])) { // IIS
            $requestUri = $_SERVER['HTTP_X_REWRITE_URL'];
        } elseif (isset($_SERVER['REQUEST_URI'])) {
            $requestUri = $_SERVER['REQUEST_URI'];
            if ($requestUri !== '' && $requestUri[0] !== '/') {
                $requestUri = preg_replace('/^(http|https):\/\/[^\/]+/i', '', $requestUri);
            }
        } elseif (isset($_SERVER['ORIG_PATH_INFO'])) { // IIS 5.0 CGI
            $requestUri = $_SERVER['ORIG_PATH_INFO'];
            if (!empty($_SERVER['QUERY_STRING'])) {
                $requestUri .= '?' . $_SERVER['QUERY_STRING'];
            }
        } else {
            throw new RequestException('Unable to determine the request URI.');
        }
        return $requestUri;
    }



    private $_hostInfo;

    /**
     * Returns the schema and host part of the current request URL.
     * The returned URL does not have an ending slash.
     * By default this is determined based on the user request information.
     * You may explicitly specify it by setting the @see hostInfo property.
     * @return string schema and hostname part (with port number if needed) of the request URL (e.g. `http://www.site.com`)
     */
    public function getHostInfo()
    {
        if ($this->_hostInfo === null) {
            $secure = $this->isSecureConnection();
            $http = $secure ? 'https' : 'http';
            if (isset($_SERVER['HTTP_HOST'])) {
                $this->_hostInfo = $http . '://' . $_SERVER['HTTP_HOST'];
            } elseif (isset($_SERVER['SERVER_NAME'])) {
                $this->_hostInfo = $http . '://' . $_SERVER['SERVER_NAME'];
                $port = $secure ? $this->getSecurePort() : $this->getPort();
                if (($port !== 80 && !$secure) || ($port !== 443 && $secure)) {
                    $this->_hostInfo .= ':' . $port;
                }
            } else {
                $this->_hostInfo = null;
            }
        }

        return $this->_hostInfo;
    }

    /**
     * @var string
     */
    private static $_schema;

    /**
     * @return string
     */
    public function getScheme()
    {
        if (static::$_schema === null) {
            static::$_schema = $this->isSecureConnection() ? 'https' : 'http';
        }

        return static::$_schema;
    }

    private $_host;

    public function getHost()
    {
        if ($this->_host === null && isset($_SERVER['SERVER_NAME'])) {
            $this->_host = Helper::getValue($_SERVER['HTTP_HOST'], $_SERVER['SERVER_NAME']);
        }

        return $this->_host;
    }

    private $_baseUrl;


    /**
     * Returns the relative URL for the application.
     * This is similar to @see getScriptUrl() except that it does not include the script file name,
     * and the ending slashes are removed.
     * @return string the relative URL for the application
     * @see setScriptUrl()
     */
    public function getBaseUrl()
    {
        if ($this->_baseUrl === null) {
            $this->_baseUrl = rtrim(dirname($this->getScriptUrl()), '\\/');
        }
        return $this->_baseUrl;
    }

    /**
     * Sets the relative URL for the application.
     * By default the URL is determined based on the entry script URL.
     * This setter is provided in case you want to change this behavior.
     * @param string $value the relative URL for the application
     */
    public function setBaseUrl($value)
    {
        $this->_baseUrl = $value;
    }

    private $_scriptUrl;

    /**
     * Returns the relative URL of the entry script.
     * The implementation of this method referenced Zend_Controller_Request_Http in Zend Framework.
     * @return string the relative URL of the entry script.
     * @throws \Exception if unable to determine the entry script URL
     */
    public function getScriptUrl()
    {
        if ($this->_scriptUrl === null) {
            $scriptFile = $this->getScriptFile();
            $scriptName = basename($scriptFile);
            if (basename($_SERVER['SCRIPT_NAME']) === $scriptName) {
                $this->_scriptUrl = $_SERVER['SCRIPT_NAME'];
            } elseif (basename($_SERVER['PHP_SELF']) === $scriptName) {
                $this->_scriptUrl = $_SERVER['PHP_SELF'];
            } elseif (isset($_SERVER['ORIG_SCRIPT_NAME']) && basename($_SERVER['ORIG_SCRIPT_NAME']) === $scriptName) {
                $this->_scriptUrl = $_SERVER['ORIG_SCRIPT_NAME'];
            } elseif (($pos = strpos($_SERVER['PHP_SELF'], '/' . $scriptName)) !== false) {
                $this->_scriptUrl = substr($_SERVER['SCRIPT_NAME'], 0, $pos) . '/' . $scriptName;
            } elseif (!empty($_SERVER['DOCUMENT_ROOT']) && strpos($scriptFile, $_SERVER['DOCUMENT_ROOT']) === 0) {
                $this->_scriptUrl = str_replace('\\', '/', str_replace($_SERVER['DOCUMENT_ROOT'], '', $scriptFile));
            } else {
                throw new \Exception('Unable to determine the entry script URL.');
            }
        }
        return $this->_scriptUrl;
    }

    /**
     * Sets the relative URL for the application entry script.
     * This setter is provided in case the entry script URL cannot be determined
     * on certain Web servers.
     * @param string $value the relative URL for the application entry script.
     */
    public function setScriptUrl($value)
    {
        $this->_scriptUrl = '/' . trim($value, '/');
    }

    private $_scriptFile;


    /**
     * Returns the entry script file path.
     * The default implementation will simply return `$_SERVER['SCRIPT_FILENAME']`.
     * @return string the entry script file path
     */
    public function getScriptFile()
    {
        return isset($this->_scriptFile) ? $this->_scriptFile : $_SERVER['SCRIPT_FILENAME'];
    }

    /**
     * Returns part of the request URL that is after the question mark.
     * @return string part of the request URL that is after the question mark
     */
    public function getQueryString()
    {
        return isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
    }

    /**
     * Return if the request is sent via secure channel (https).
     * @return boolean if the request is sent via secure channel (https)
     */
    public function isSecureConnection()
    {
        return isset($_SERVER['HTTPS']) && (strcasecmp($_SERVER['HTTPS'], 'on') === 0 || $_SERVER['HTTPS'] == 1)
               || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strcasecmp($_SERVER['HTTP_X_FORWARDED_PROTO'], 'https') === 0;
    }

    /**
     * Returns the server name.
     * @return string server name
     */
    public function getServerName()
    {
        return $_SERVER['SERVER_NAME'];
    }

    /**
     * Returns the server port number.
     * @return integer server port number
     */
    public function getServerPort()
    {
        return (int)$_SERVER['SERVER_PORT'];
    }

    /**
     * Returns the URL referrer, null if not present
     * @return string URL referrer, null if not present
     */
    public function getReferrer()
    {
        return isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
    }

    /**
     * Returns the user agent, null if not present.
     * @return string user agent, null if not present
     */
    public function getUserAgent()
    {
        return isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
    }

    /**
     * Returns the user IP address.
     * @return string user IP address
     */
    public function getUserIP()
    {
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
    }

    /**
     * Returns the user host name, null if it cannot be determined.
     * @return string user host name, null if cannot be determined
     */
    public function getUserHost()
    {
        return isset($_SERVER['REMOTE_HOST']) ? $_SERVER['REMOTE_HOST'] : null;
    }

    /**
     * @return string the username sent via HTTP authentication, null if the username is not given
     */
    public function getAuthUser()
    {
        return isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : null;
    }

    /**
     * @return string the password sent via HTTP authentication, null if the password is not given
     */
    public function getAuthPassword()
    {
        return isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : null;
    }

    private static $_port;


    /**
     * Returns the port to use for insecure requests.
     * Defaults to 80, or the port specified by the server if the current
     * request is insecure.
     * @return integer port number for insecure requests.
     * @see setPort()
     */
    public function getPort()
    {
        if (self::$_port === null) {
            self::$_port = !$this->isSecureConnection() && isset($_SERVER['SERVER_PORT']) ? (int)$_SERVER['SERVER_PORT'] : 80;
        }
        return self::$_port;
    }

    /**
     * Sets the port to use for insecure requests.
     * This setter is provided in case a custom port is necessary for certain
     * server configurations.
     * @param integer $value port number.
     */
    public function setPort($value)
    {
        if ($value != self::$_port) {
            self::$_port = (int)$value;
            $this->_hostInfo = null;
        }
    }

    private static $_securePort;

    /**
     * Returns the port to use for secure requests.
     * Defaults to 443, or the port specified by the server if the current
     * request is secure.
     * @return integer port number for secure requests.
     * @see setSecurePort()
     */
    public function getSecurePort()
    {
        if (self::$_securePort === null) {
            self::$_securePort = $this->isSecureConnection() && isset($_SERVER['SERVER_PORT']) ? (int)$_SERVER['SERVER_PORT'] : 443;
        }
        return self::$_securePort;
    }

    /**
     * Sets the port to use for secure requests.
     * This setter is provided in case a custom port is necessary for certain
     * server configurations.
     * @param integer $value port number.
     */
    public function setSecurePort($value)
    {
        if ($value != self::$_securePort) {
            self::$_securePort = (int)$value;
            $this->_hostInfo = null;
        }
    }

    /**
     * Is methods request
     *
     * @param array $methods - names of methods
     * @return bool
     */
    public function isMethods(array $methods)
    {
        return in_array($this->getMethod(), $methods, true);
    }

    /**
     * Is ips request
     *
     * @param array $ips - ips
     * @return bool
     */
    public function isIps(array $ips)
    {
        return in_array($_SERVER['REMOTE_ADDR'], $ips, true);
    }

    /**
     * Returns the method of the current request (e.g. GET, POST, HEAD, PUT, PATCH, DELETE).
     * @return string request method, such as GET, POST, HEAD, PUT, PATCH, DELETE.
     * The value returned is turned into upper case.
     */
    public function getMethod()
    {
        if (isset($_POST[$this->methodVar])) {
            return strtoupper($_POST[$this->methodVar]);
        } elseif (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
            return strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
        } else {
            return isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';
        }
    }

    /**
     * Returns whether this is a GET request.
     * @return boolean whether this is a GET request.
     */
    public function isGet()
    {
        return $this->getMethod() === 'GET';
    }

    /**
     * Returns whether this is an OPTIONS request.
     * @return boolean whether this is a OPTIONS request.
     */
    public function isOptions()
    {
        return $this->getMethod() === 'OPTIONS';
    }

    /**
     * Returns whether this is a HEAD request.
     * @return boolean whether this is a HEAD request.
     */
    public function isHead()
    {
        return $this->getMethod() === 'HEAD';
    }

    /**
     * Returns whether this is a POST request.
     * @return boolean whether this is a POST request.
     */
    public function isPost()
    {
        return $this->getMethod() === 'POST';
    }

    /**
     * Returns whether this is a DELETE request.
     * @return boolean whether this is a DELETE request.
     */
    public function isDelete()
    {
        return $this->getMethod() === 'DELETE';
    }

    /**
     * Returns whether this is a PUT request.
     * @return boolean whether this is a PUT request.
     */
    public function isPut()
    {
        return $this->getMethod() === 'PUT';
    }

    /**
     * Returns whether this is a PATCH request.
     * @return boolean whether this is a PATCH request.
     */
    public function isPatch()
    {
        return $this->getMethod() === 'PATCH';
    }

    /**
     * Returns whether this is an AJAX (XMLHttpRequest) request.
     * @return boolean whether this is an AJAX (XMLHttpRequest) request.
     */
    public function isAjax()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }

    /**
     * Returns whether this is a PJAX request
     * @return boolean whether this is a PJAX request
     */
    public function isPjax()
    {
        return $this->isAjax() && !empty($_SERVER['HTTP_X_PJAX']);
    }

    /**
     * Returns whether this is an Adobe Flash or Flex request.
     * @return boolean whether this is an Adobe Flash or Adobe Flex request.
     */
    public function isFlash()
    {
        return isset($_SERVER['HTTP_USER_AGENT']) &&
               (stripos($_SERVER['HTTP_USER_AGENT'], 'Shockwave') !== false || stripos($_SERVER['HTTP_USER_AGENT'], 'Flash') !== false);
    }

    /**
     * Parse vars of HEAD, PUT, PATCH, DELETE
     */
    public function parseRequest()
    {
        $method = $this->getMethod();
        if (empty($GLOBALS['_' . $method]) && array_key_exists($method, ['HEAD' => 0, 'POST' => 1, 'PUT' => 2, 'PATCH' => 3, 'DELETE' => 4])) {

            $stream = trim(file_get_contents('php://input'));
            if ($this->getContentType() === 'application/json' || Json::is($stream)) {
                $array = Json::decode($stream, true);
            } else {
                parse_str($stream, $array);
            }

            $GLOBALS['_' . $method] = $array;
            // Add these request vars into _REQUEST, mimicing default behavior, PUT/DELETE will override existing COOKIE/GET vars
            $_REQUEST = $array + $_REQUEST;
        }
    }


    private $_homeUrl;

    /**
     * @return string the homepage URL
     */
    public function getHomeUrl()
    {
        if ($this->_homeUrl === null) {
            if ($this->showScriptName) {
                return $this->getScriptUrl();
            } else {
                return $this->getBaseUrl() . '/';
            }
        } else {
            return $this->_homeUrl;
        }
    }

    /**
     * @param string $value the homepage URL
     */
    public function setHomeUrl($value)
    {
        $this->_homeUrl = $value;
    }

    /**
     * Sanitize all request-values.
     *
     * @param mixed $input
     * @param Sanitize $sanitize
     * @return mixed
     */
    protected static function prepareAll($input, Sanitize $sanitize = null)
    {
        if (empty($input)) {
            return $input;
        }
        if (!isset($sanitize)) {
            $sanitize = Sanitize::allOf(Sanitize::removeTags()->trim()->toType());
        }
        return $sanitize->sanitize($input);
    }

    /**
     * Sanitize request-value.
     *
     * @param string      $method - method request
     * @param string      $name - name of request-value
     * @param mixed  $default
     * @param Sanitize $sanitize
     * @return null
     */
    protected static function prepareValue($method, $name, $default = null, Sanitize $sanitize = null)
    {
        if (!isset($GLOBALS[$method][$name])) {
            return $default;
        }
        if (!isset($sanitize)) {
            $sanitize = Sanitize::removeTags()->trim()->toType();
        }
        return $sanitize->sanitize($GLOBALS[$method][$name]);
    }
}