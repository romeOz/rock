<?php

namespace rock\filters;

use rock\Rock;

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

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        if (empty($this->actions) || empty($action)) {
            return true;
        }
        $request = Rock::$app->request;

        if (isset($this->actions['*'])) {
            $verbs = $this->actions['*'];
            if ($request->isMethods($this->actions['*'])) {
                return true;
            }
        }
        if (isset($this->actions[$action])) {
            $verbs = $this->actions[$action];
            if ($request->isMethods($this->actions[$action])) {
                return true;
            }
        }

        if (!empty($verbs)) {
            // http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.7
            $verbs = implode(', ', $verbs);
            $response = Rock::$app->response;
            $response->getHeaders()->set('Allow', $verbs);
            $response->setStatusCode(405);
            if ($this->throwException === true) {
                throw new VerbsFilterException('Method Not Allowed. This url can only handle the following request methods: ' . $verbs . '.');
            }
        }

        return false;
    }
}