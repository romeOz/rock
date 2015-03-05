<?php

namespace rock\filters;

use rock\helpers\Instance;
use rock\request\Request;
use rock\response\Response;

/**
 * VerbFilter is an action filter that filters by HTTP request methods.
 *
 * It allows to define allowed HTTP request methods for each action and will throw
 * an HTTP 405 error when the method is not allowed.
 *
 * To use VerbFilter, declare it in the `behaviors()` method of your controller class.
 * For example, the following declarations will define a typical set of allowed
 * request methods for REST CRUD actions.
 *
 * ```php
 * public function behaviors()
 * {
 *     return [
 *         'verbs' => [
 *             'class' => VerbFilter::className(),
 *             'actions' => [
 *                 'actionIndex'  => [Request::GET],
 *                 'actionView'   => [Request::GET],
 *                 'actionCreate' => [Request::GET, Request::POST],
 *                 'actionUpdate' => [Request::GET, Request::PUT, Request::POST],
 *                 'actionDelete' => [Request::POST, Request::DELETE],
 *             ],
 *         ],
 *     ];
 * }
 * ```
 *
 * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.7
 */
class VerbFilter extends ActionFilter
{
    /**
     * @var Request|string|array the current request. If not set, the `request` application component will be used.
     */
    public $request = 'request';
    /**
     * @var Response|string|array the response to be sent. If not set, the `response` application component will be used.
     */
    public $response = 'response';
    /**
     * @var array this property defines the allowed request methods for each action.
     * For each action that should only support limited set of request methods
     * you add an entry with the action id as array key and an array of
     * allowed methods (e.g. `GET`, `HEAD`, `PUT`) as the value.
     * If an action is not listed all request methods are considered allowed.
     *
     * You can use '*' to stand for all actions. When an action is explicitly
     * specified, it takes precedence over the specification given by '*'.
     *
     * For example,
     *
     * ```php
     * [
     *   'actionCreate' => [Request::GET, Request::POST],
     *   'actionUpdate' => [Request::GET, Request::PUT, Request::POST],
     *   'actionDelete' => [Request::POST, Request::DELETE],
     *   '*' => [Request::GET],
     * ]
     * ```
     */
    public $actions = [];
    public $throwException = false;

    public function init()
    {
        $this->request = Instance::ensure($this->request, '\rock\request\Request');
        $this->response = Instance::ensure($this->response, '\rock\response\Response');
    }

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        if (empty($this->actions) || empty($action)) {
            return true;
        }

        if (isset($this->actions['*'])) {
            $verbs = $this->actions['*'];
            if ($this->request->isMethods($this->actions['*'])) {
                return true;
            }
        }
        if (isset($this->actions[$action])) {
            $verbs = $this->actions[$action];
            if ($this->request->isMethods($this->actions[$action])) {
                return true;
            }
        }

        if (!empty($verbs)) {
            // http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.7
            $verbs = implode(', ', $verbs);
            $this->response->getHeaders()->set('Allow', $verbs);
            $this->response->setStatusCode(405);
            if ($this->throwException === true) {
                throw new VerbsFilterException('Method Not Allowed. This url can only handle the following request methods: ' . $verbs . '.');
            }
        }

        return false;
    }
}