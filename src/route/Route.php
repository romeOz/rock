<?php

namespace rock\route;


use rock\access\ErrorsInterface;
use rock\access\ErrorsTrait;
use rock\base\Alias;
use rock\components\ComponentsInterface;
use rock\components\ComponentsTrait;
use rock\core\Controller;
use rock\di\Container;
use rock\events\Event;
use rock\helpers\ArrayHelper;
use rock\helpers\Helper;
use rock\helpers\Instance;
use rock\helpers\StringHelper;
use rock\request\Request;
use rock\request\RequestInterface;
use rock\response\Response;
use rock\Rock;
use rock\sanitize\Sanitize;

class Route implements RequestInterface, ErrorsInterface, ComponentsInterface, \ArrayAccess
{
    use ComponentsTrait;
    use ErrorsTrait;

    const EVENT_BEGIN_ROUTER = 'beginRoute';
    const EVENT_END_ROUTER = 'endRoute';
    const EVENT_RULE_ROUTE = 'ruleRoute';

    const ANY = '*';
    const REST = 1;

    const FILTER_SCHEME = 'scheme';
    const FILTER_HOST   = 'host';
    const FILTER_PATH   = 'path';
    const FILTER_QUERY  = 'query';
    const FILTER_GET    = 'get';
    const FILTER_POST   = 'post';
    const FILTER_PUT   = 'put';
    const FILTER_DELETE = 'delete';

    public $rules = [];
    /** @var  callable */
    public $success;
    /** @var  callable */
    public $fail;
    public $RESTHandlers = [];
    public $sanitizeRules = ['removeTags', 'trim', ['call' => 'urldecode'],'toType'];
    /** @var  Request|string|array */
    public $request = 'request';
    /** @var  Response|string|array */
    public $response = 'response';
    /** @var  array */
    protected $data = [];
    protected $params = [];
    protected $errors = 0;

    public function init()
    {
        $this->request = Instance::ensure($this->request, Request::className());
        $this->response = Instance::ensure($this->response, Response::className());

        $this->calculateData();
        $handlers = $this->defaultRESTHandlers();
        $this->RESTHandlers = empty($this->RESTHandlers) ? $handlers : array_merge($handlers, $this->RESTHandlers);
    }

    public function run($new = false)
    {
        $self = $this;
        if ($new) {
            /** @var static $self */
            $self = Instance::ensure(static::className());
            $self->response = $this->response;
        }
        Event::trigger($self, self::EVENT_BEGIN_ROUTER);
        $self->provide();
        Event::trigger($self, self::EVENT_END_ROUTER);
    }

    /**
     * Set config scope
     *
     * @param string $path path to config
     * @param bool   $clear clear DIC.
     * @throws RouteException
     * @throws \Exception
     */
    public static function setConfigScope($path, $clear = true)
    {
        $path = Alias::getAlias($path);
        if (!file_exists($path) || (!$config = require($path))) {
            throw new RouteException(RouteException::UNKNOWN_FILE, ['path' => $path]);
        }
        if ($clear) {
            Container::removeAll();
        }
        $components = $config['components'] ? : [];
        if (class_exists('\rock\Rock')) {
            unset($config['components']);
            Rock::$components = $components;
            Rock::$config = $config;
        }
        Container::addMulti($components);
    }

    /**
     * Add route
     *
     * @param array|string $verbs
     * @param string|array          $pattern
     * @param callable|array        $handler
     * @param callable|array|null   $filters
     * @return boolean
     */
    public function addRoute($verbs, $pattern, $handler, $filters = null)
    {
        if (!$this->isRoute($verbs, $pattern, $handler, $filters)) {
            $this->initFail();
            return false;
        }

        return true;
    }

    protected function isRoute($verbs, $pattern, $handler, $filters = null)
    {
        /**
         * default rule or equals rule
         */
        if ($this->isPattern($pattern) === true) {
            return $this->isRequests(is_string($verbs) ? [$verbs] : $verbs, $handler, $filters);
        }

        $this->errors |= self::E_NOT_FOUND;

        return false;
    }

    /**
     * Add route by any request methods
     *
     * @param string|array $pattern
     * @param callable|array  $handler
     * @param callable|array|null     $filters
     * @return boolean
     */
    public function any($pattern, $handler, $filters = null)
    {
        return $this->addRoute(self::ANY, $pattern, $handler, $filters);
    }

    /**
     * Add route by GET
     *
     * @param string|array $pattern
     * @param callable|array     $handler
     * @param callable|array|null     $filters
     * @return boolean
     */
    public function get($pattern, $handler, $filters = null)
    {
        return $this->addRoute(self::GET, $pattern, $handler, $filters);
    }

    /**
     * Add route by POST
     *
     * @param string|array $pattern
     * @param callable|array     $handler
     * @param callable|array|null     $filters
     * @return boolean
     */
    public function post($pattern, $handler, $filters = null)
    {
        return $this->addRoute(self::POST, $pattern, $handler, $filters);
    }

    /**
     * Add route by PUT
     *
     * @param string|array $pattern
     * @param callable|array     $handler
     * @param callable|array|null     $filters
     * @return boolean
     */
    public function put($pattern, $handler, $filters = null)
    {
        return $this->addRoute(self::PUT, $pattern, $handler, $filters);
    }

    /**
     * Add route by DELETE
     *
     * @param string|array $pattern
     * @param callable|array     $handler
     * @param callable|array|null     $filters
     * @return boolean
     */
    public function delete($pattern, $handler, $filters = null)
    {
        return $this->addRoute(self::DELETE, $pattern, $handler, $filters);
    }

    /**
     * Add routers
     *
     * @param string   $url
     * @param string   $controller
     * @param array $filters
     * @return boolean
     */
    public function REST($url, $controller, array $filters = [])
    {
        if (!$this->isREST($url, $controller, $filters)) {
            $this->initFail();
            return false;
        }

        return true;
    }

    /**
     * @param callable $success
     * @return $this
     */
    public function success(callable $success)
    {
        $this->success = $success;
        return $this;
    }

    /**
     * @param callable $fail
     * @return $this
     */
    public function fail(callable $fail)
    {
        $this->fail = $fail;
        return $this;
    }

    /**
     * Returns list route-params.
     * @return array
     */
    public function getParam()
    {
        return $this->params;
    }

    /**
     * Returns route-param.
     * @param string $name name of param
     * @return mixed
     */
    public function __get($name)
    {
        return isset($this->params[$name]) ? $this->params[$name] : null;
    }

    /**
     * Returns route-param.
     * @param string $name name of param
     * @return mixed
     */
    public function offsetGet($name)
    {
        return $this->$name;
    }

    /**
     * Exists route-param.
     * @param string $name name of param
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->params[$name]);
    }

    /**
     * Exists route-param.
     * @param string $name name of param
     * @return bool
     */
    public function offsetExists($name)
    {
        return isset($this->$name);
    }

    /**
     * Set route-param.
     * @param string $name name of param
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        $this->params[$name] = $value;
    }

    /**
     * Set route-param.
     * @param string $name name of param
     * @param mixed $value
     */
    public function offsetSet($name, $value)
    {
        $this->$name = $value;
    }

    /**
     * Deleting route-param.
     * @param string $name name of param
     */
    public function __unset($name)
    {
        unset($this->params[$name]);
    }

    /**
     * Deleting route-param.
     * @param string $name name of param
     */
    public function offsetUnset($name)
    {
        unset($this->$name);
    }

    protected function provide()
    {
        if (!empty($this->rules)) {
            if (!$this->provideRules($this->rules)) {
                $this->initFail();
            }
            return;
        }
        throw new RouteException(RouteException::UNKNOWN_PROPERTY, ['name' => 'rules']);
    }

    protected function isPattern($pattern)
    {
        if (is_array($pattern)) {
            foreach ($pattern as $request => $compared) {
                if (!$inputs = $this->getDataRequest($request)) {
                    return false;
                }
                foreach ((array)$compared as $key => $compare) {
                    if ($this->validPattern($compare, $inputs[$key]) === false) {
                        return false;
                    }
                }
            }

            return true;
        }

        return $this->validPattern($pattern, $this->data['path']);
    }

    /**
     * Get format.
     *
     * @param $key
     * @return array
     * @throws RouteException
     */
    protected function getDataRequest($key)
    {
        switch ($key) {
            case self::FILTER_SCHEME:
                return [$this->data['scheme']];
            case self::FILTER_HOST:
                return [$this->data['host']];
            case self::FILTER_PATH:
                return [$this->data['path']];
            case self::FILTER_QUERY:
                return isset($this->data['query']) ? [$this->data['query']] : [];
            case self::FILTER_GET:
                return $GLOBALS['_GET'] ? : [];
            case self::FILTER_POST:
                return $GLOBALS['_POST'] ? : [];
            case self::FILTER_PUT:
                return $GLOBALS['_PUT'] ? : [];
            case self::FILTER_DELETE:
                return $GLOBALS['_DELETE'] ? : [];
            default:
                throw new RouteException(RouteException::UNKNOWN_FORMAT, ['format' => $key]);
        }
    }

    protected function validPattern($pattern, $input)
    {
        if ($pattern === '*' || $pattern === $input ||
            (StringHelper::isRegexp($pattern) && $this->match($pattern, $input))
        ) {
            return true;
        }

        return false;
    }

    /**
     * Match url.
     *
     * @param string $pattern regexp-pattern
     * @param string $url
     * @return bool
     */
    protected function match($pattern, $url)
    {
        if (preg_match($pattern, $url, $matches)) {
            $result = [];
            foreach ($matches as $key => $value) {
                if (is_int($key)) {
                    continue;
                }
                $result[$key] = Sanitize::rules($this->sanitizeRules)->sanitize($value);
            }
            $this->params = array_merge($this->params, $result);

            return true;
        }

        return false;
    }

    /**
     * Available Request Method.
     *
     * @param string[] $verbs
     * @return bool
     */
    protected function hasVerbs(array $verbs)
    {
        if (in_array('*', $verbs, true)) {
            return true;
        }

        return $this->request->isMethods($verbs);
    }

    protected function initSuccess()
    {
        if (!isset($this->success)) {
            return;
        }
        call_user_func($this->success, $this);
    }

    protected function initFail()
    {
        if (!isset($this->fail)) {
            return;
        }
        call_user_func($this->fail, $this);
    }

    protected function provideRules(array $rules)
    {
        foreach ($rules as $rule) {
            if ($rule[0] === self::REST) {
                array_shift($rule);
                if (empty($rule[2])) {
                    $rule[2] = [];
                }
                list($url, $controller, $filters) = $rule;
                if ($this->isREST($url, $controller, $filters)) {
                    return true;
                }
                continue;
            }

            if (!isset($rule[3])) {
                $rule[3] = null;
            }
            list($verbs, $pattern, $handler, $filters) = $rule;
            if ($this->isRoute($verbs, $pattern, $handler, $filters)) {
                $this->errors = 0;
                return true;
            } else {
                $this->errors |= $this->errors;
            }
        }
        return false;
    }

    protected function isREST($url, $controller, $filters)
    {
        $handlers = ArrayHelper::only(
            $this->RESTHandlers,
            Helper::getValue($filters['only'], []),
            Helper::getValue($filters['exclude'], [])
        );

        foreach ($handlers as $value) {
            if (!isset($value[3])) {
                $value[3] = null;
            }
            list ($verbs, $pattern, $handler, $_filters) = $value;
            $filters = !empty($filters['filters']) ? $filters['filters'] : $_filters ;

            if (StringHelper::isRegexp($pattern)) {
                $url = preg_quote($url, '/');
                $pattern = "~{$pattern}";
            }
            $pattern = str_replace('{url}', $url, $pattern);
            $this->params['controller'] = $controller;
            if ($this->isRoute($verbs, $pattern, $handler, $filters)) {
                $this->errors = 0;
                return true;
            } else {
                $this->errors |= $this->errors;
            }
        }
        return false;
    }

    /**
     * @param array    $verbs
     * @param callable|array $handler
     * @param callable $filters
     * @return bool
     */
    protected function isRequests(array $verbs, $handler, $filters = null)
    {
        if (!static::hasVerbs($verbs)) {
            $this->errors |= self::E_VERBS;
            return false;
        }
        if (isset($filters)) {
            if ($filters instanceof \Closure) {
                if (!$this->isBool($filters)) {
                    return false;
                }
            } elseif (is_array($filters)) {
                if (!$this->isBehavior($filters)) {
                    return false;
                }
            }
        }

        $this->errors = 0;
        $this->initSuccess();
        $this->callAction($handler);

        return true;
    }

    protected function isBool(callable $callback)
    {
        $is = (bool)call_user_func($callback, $this);
        if (!$is) {
            $this->errors |= self::E_NOT_FOUND;
            return false;
        }

        return true;
    }

    /**
     * @param array $behaviors
     * @return bool
     */
    protected function isBehavior(array $behaviors)
    {
        $result = null;
        $this->attachBehaviors($behaviors);
        $event = new RouteEvent();
        $this->trigger(self::EVENT_RULE_ROUTE, $event);

        if (!$event->isValid) {
            $this->errors |= $event->errors;
            return false;
        }
        return true;
    }

    protected function callAction($handler)
    {
        if ($handler instanceof \Closure) {
            $handler = call_user_func($handler, $this);
        }

        if (!is_array($handler)) {
            return;
        }

        list($class, $method) = $handler;
        if (is_string($class)) {
            if (!class_exists($class)) {
                throw new RouteException(RouteException::UNKNOWN_CLASS, ['class' => $class]);
            }
            $class = new $class;
        }

        if (!method_exists($class, $method)) {
            $class = get_class($class);
            throw new RouteException(RouteException::UNKNOWN_METHOD, ['method' => "{$class}::{$method}"]);
        }
        if ($class instanceof Controller) {
            $class->response = $this->response;
        }

        $args = $this->injectArgs($class, $method);
        array_unshift($args, $method);
        $result = call_user_func_array([$class, 'method'], $args);//$class->method($method, $this);
        // echo other format json, xml...
        if (isset($result)) {
            $this->response->data = $result;
        }
    }

    protected function injectArgs($class, $method)
    {
        $reflectionMethod = new \ReflectionMethod($class, $method);
        $args = [];
        $i = 1;
        foreach ($reflectionMethod->getParameters() as $param) {
            if (!$class = $param->getClass()) {
                if ($param->isDefaultValueAvailable()) {
                    $args[] = $param->getDefaultValue();
                } else {
                    throw new RouteException("Argument #{$i} must be instance");
                }

                continue;
            }

            if ($class->isInstance($this)) {
                $args[] = $this;
                continue;
            }

            $args[] = Container::load($class->getName());
            ++$i;
        }
        return $args;
    }

    protected function calculateData()
    {
        $this->data = parse_url($this->request->getAbsoluteUrl());
    }

    protected function defaultRESTHandlers()
    {
        return [
            'index' => [
                self::GET,
                '/{url}/',
                function(Route $route) {
                    return call_user_func([Container::load($route['controller']), 'actionIndex'], $route);
                }
            ],
            'create' => [
                self::GET,
                '/{url}/create/',
                function(Route $route) {
                    return call_user_func([Container::load($route['controller']), 'actionCreate'], $route);
                }
            ],
            'store' => [
                self::POST,
                '/{url}/',
                function(Route $route) {
                    return call_user_func([Container::load($route['controller']), 'actionStore'], $route);
                }
            ],
            'show' => [
                self::GET,
                '~/^\/{url}\/(?P<id>[^\/]+)$/',
                function(Route $route) {
                    return call_user_func([Container::load($route['controller']), 'actionShow'], $route);
                }
            ],
            'edit' => [
                self::GET,
                '~/^\/{url}\/(?P<id>[^\/]+)\/edit\/$/',
                function(Route $route) {
                    return call_user_func([Container::load($route['controller']), 'actionEdit'], $route);
                }
            ],
            'update' => [
                [self::PUT, self::PATCH],
                '~/^\/{url}\/(?P<id>[^\/]+)$/',
                function(Route $route) {
                    return call_user_func([Container::load($route['controller']), 'actionUpdate'], $route);
                }
            ],
            'delete' => [
                self::DELETE,
                '~/^\/{url}\/(?P<id>[^\/]+)$/',
                function(Route $route) {
                    return call_user_func([Container::load($route['controller']), 'actionDelete'], $route);
                }
            ]
        ];
    }
}