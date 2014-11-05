<?php

namespace rock\route;


use rock\base\ComponentsTrait;
use rock\base\Config;
use rock\di\Container;
use rock\event\Event;
use rock\filters\AccessFilter;
use rock\helpers\ArrayHelper;
use rock\helpers\Helper;
use rock\helpers\String;
use rock\request\RequestInterface;
use rock\Rock;
use rock\sanitize\Sanitize;

class Route implements RequestInterface, ErrorsInterface
{
    use ComponentsTrait;
    use ErrorsTrait;

    const EVENT_BEGIN_ROUTER = 'beginRoute';
    const EVENT_END_ROUTER = 'endRoute';
    const EVENT_RULE_ROUTE = 'ruleRoute';

    const ANY = '*';
    const REST = 1;

    const FORMAT_SCHEME = 1;
    const FORMAT_HOST   = 2;
    const FORMAT_PATH   = 4;
    const FORMAT_QUERY  = 8;
    const FORMAT_ALL    = 15;

    /** @var  array */
    public $data = [];
    public $rules = [];
    /** @var  array|\Closure */
    public $success;
    /** @var  array|\Closure */
    public $fail;
    public $RESTHandlers = [];
    public static $defaultFilters = ['removeTags', 'trim', ['call' => 'urldecode'],'toType'];

    protected $errors = 0;

    public function init()
    {
        $this->calculateData();
        $handlers = $this->defaultRESTHandlers();
        $this->RESTHandlers = empty($this->RESTHandlers) ? $handlers : array_merge($handlers, $this->RESTHandlers);
    }


    public function run()
    {
        Event::trigger($this, self::EVENT_BEGIN_ROUTER);
        $this->provide();
        Event::trigger($this, self::EVENT_END_ROUTER);
    }

    /**
     * SetConfigScope
     *
     * @param string $path - path config
     * @throws Exception
     */
    public static function setConfigScope($path)
    {
        $path = Rock::getAlias($path);
        if (!file_exists($path) || (!$configs = require($path))) {
            throw new Exception(Exception::CRITICAL, Exception::UNKNOWN_FILE, ['path' => $path]);
        }
        Config::set($configs);
        Container::addMulti($configs['_components']);
    }

    /**
     * Add route
     *
     * @param array|string $verbs
     * @param string|array          $pattern
     * @param callable              $handler
     * @param callable|array|null     $filters
     * @return boolean
     */
    public function addRoute($verbs, $pattern, \Closure $handler, $filters = null)
    {
        if (!$this->isRoute($verbs, $pattern, $handler, $filters)) {
            $this->initFail();
            return false;
        }

        return true;
    }

    protected function isRoute($verbs, $pattern, \Closure $handler, $filters = null)
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
     * @param callable     $handler
     * @param callable|array|null     $filters
     * @return boolean
     */
    public function any($pattern, \Closure $handler, $filters = null)
    {
        return $this->addRoute(self::ANY, $pattern, $handler, $filters);
    }

    /**
     * Add route by GET
     *
     * @param string|array $pattern
     * @param callable     $handler
     * @param callable|array|null     $filters
     * @return boolean
     */
    public function get($pattern, \Closure $handler, $filters = null)
    {
        return $this->addRoute(self::GET, $pattern, $handler, $filters);
    }

    /**
     * Add route by POST
     *
     * @param string|array $pattern
     * @param callable     $handler
     * @param callable|array|null     $filters
     * @return boolean
     */
    public function post($pattern, \Closure $handler, $filters = null)
    {
        return $this->addRoute(self::POST, $pattern, $handler, $filters);
    }

    /**
     * Add route by PUT
     *
     * @param string|array $pattern
     * @param callable     $handler
     * @param callable|array|null     $filters
     * @return boolean
     */
    public function put($pattern, \Closure $handler, $filters = null)
    {
        return $this->addRoute(self::PUT, $pattern, $handler, $filters);
    }

    /**
     * Add route by DELETE
     *
     * @param string|array $pattern
     * @param callable     $handler
     * @param callable|array|null     $filters
     * @return boolean
     */
    public function delete($pattern, \Closure $handler, $filters = null)
    {
        return $this->addRoute(self::DELETE, $pattern, $handler, $filters);
    }

    protected function defaultRESTHandlers()
    {
        return [
            'index' => [
                self::GET,
                '/{url}/',
                function(array $dataRoute) {
                    return call_user_func([Rock::factory($dataRoute['controller']), 'actionIndex'], $dataRoute);
                }
            ],
            'create' => [
                self::GET,
                '/{url}/create/',
                function(array $dataRoute) {
                    return call_user_func([Rock::factory($dataRoute['controller']), 'actionCreate'], $dataRoute);
                }
            ],
            'store' => [
                self::POST,
                '/{url}/',
                function(array $dataRoute) {
                    return call_user_func([Rock::factory($dataRoute['controller']), 'actionStore'], $dataRoute);
                }
            ],
            'show' => [
                self::GET,
                '~/^\/{url}\/(?P<id>[^\/]+)$/',
                function(array $dataRoute) {
                    return call_user_func([Rock::factory($dataRoute['controller']), 'actionShow'], $dataRoute);
                }
            ],
            'edit' => [
                self::GET,
                '~/^\/{url}\/(?P<id>[^\/]+)\/edit\/$/',
                function(array $dataRoute) {
                    return call_user_func([Rock::factory($dataRoute['controller']), 'actionEdit'], $dataRoute);
                }
            ],
            'update' => [
                [self::PUT, self::PATCH],
                '~/^\/{url}\/(?P<id>[^\/]+)$/',
                function(array $dataRoute) {
                    return call_user_func([Rock::factory($dataRoute['controller']), 'actionUpdate'], $dataRoute);
                }
            ],
            'delete' => [
                self::DELETE,
                '~/^\/{url}\/(?P<id>[^\/]+)$/',
                function(array $dataRoute) {
                    return call_user_func([Rock::factory($dataRoute['controller']), 'actionDelete'], $dataRoute);
                }
            ]
        ];
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
     * @param array|\Closure $success
     * @return $this
     */
    public function success($success)
    {
        $this->success = $success;
        return $this;
    }

    /**
     * @param array|\Closure $fail
     * @return $this
     */
    public function fail($fail)
    {
        $this->fail = $fail;
        return $this;
    }

    /**
     * Manager URL by Application.
     *
     * @throws Exception
     */
    protected function provide()
    {
        if (!empty($this->rules)) {
            if (!$this->provideRules($this->rules)) {
                $this->initFail();
            }
            return;
        }
        throw new Exception(Exception::CRITICAL, Exception::UNKNOWN_PROPERTY, ['name' => 'rules']);
    }

    protected function isPattern($pattern)
    {
        if (is_array($pattern)) {
            foreach ($pattern as $key => $value) {
                if (!$format = $this->getFormat($key)) {
                    return false;
                }
                if ($this->validPattern($value, $format) === false) {
                    return false;
                }
            }

            return true;
        }

        return $this->validPattern($pattern, $this->data['path']);
    }

    /**
     * Get format.
     * @param $key
     * @return string
     * @throws Exception
     */
    protected function getFormat($key)
    {
        switch ($key) {
            case self::FORMAT_SCHEME:
                return $this->data['scheme'];
            case self::FORMAT_HOST:
                return $this->data['host'];
            case self::FORMAT_PATH:
                return $this->data['path'];
            case self::FORMAT_QUERY:
                return Helper::getValueIsset($this->data['query']);
            default:
                throw new Exception(Exception::CRITICAL, Exception::UNKNOWN_FORMAT, ['format' => $key]);
        }
    }

    protected function validPattern($pattern, $url)
    {
        if ($pattern === '*' || $pattern === $url ||
            (String::isRegexp($pattern) && $this->match($pattern, $url))
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
                $result[$key] = Sanitize::rules(static::$defaultFilters)->sanitize($value);
            }
            $this->data = array_merge($this->data, $result);

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

        return $this->Rock->request->isMethods($verbs);
    }

    protected function initSuccess()
    {
        if (!isset($this->success)) {
            return;
        }
        if ($this->success instanceof \Closure) {
            $this->success = [$this->success];
        }
        $this->success[1] = Helper::getValueIsset($this->success[1], []);
        list($function, $args) = $this->success;
        $route = clone $this;
        $route->data = array_merge(['callbackParams' => $args], $this->data);
        call_user_func($function, $route);
    }

    protected function initFail()
    {
        if (!isset($this->fail)) {
            return;
        }
        if ($this->fail instanceof \Closure) {
            $this->fail = [$this->fail];
        }

        $this->fail[1] = Helper::getValueIsset($this->fail[1], []);
        list($function, $args) = $this->fail;
        $route = clone $this;
        $route->data = array_merge(['callbackParams' => $args], $this->data);
        call_user_func($function, $route);
    }

    protected function provideRules(array $rules)
    {
        foreach ($rules as $rule) {
            //$this->calculateData();

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
            $route = clone $this;
            if ($route->isRoute($verbs, $pattern, $handler, $filters)) {
                $this->errors = 0;
                return true;
            } else {
                $this->errors |= $route->errors;
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

            if (String::isRegexp($pattern)) {
                $url = preg_quote($url, '/');
                $pattern = "~{$pattern}";
            }
            $pattern = str_replace('{url}', $url, $pattern);
            $route = clone $this;
            $route->data['controller'] = $controller;
            if ($route->isRoute($verbs, $pattern, $handler, $filters)) {
                $this->errors = 0;
                return true;
            } else {
                $this->errors |= $route->errors;
            }
        }
        return false;
    }

    /**
     * @param array    $verbs
     * @param callable $handler
     * @param callable $filters
     * @return bool
     */
    protected function isRequests(array $verbs, \Closure $handler, $filters = null)
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
        $this->defaultScope();
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
//        if (!$behaviors->before()) {
//
//            $this->errors |= $behaviors->getAccessErrors();
//
//            return false;
//        }
//
//        $behaviors->after(null/*, $result*/);
//
//        return true;
    }

    protected function callAction(\Closure $handler)
    {
        $result = call_user_func($handler, $this->data);

        // echo other format json, xml...
        if (isset($result)) {
            Rock::$app->response->data = $result;
        }
    }

    protected function defaultScope()
    {
        $this->data['scope'] = str_replace('\\', '', strstr(Rock::getAlias('@ns'), '\\'));
    }

    protected function calculateData()
    {
        $this->data = parse_url($this->Rock->request->getAbsoluteUrl());
        $this->defaultScope();
    }
}