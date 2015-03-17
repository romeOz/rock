<?php

namespace rock\filters;

use rock\helpers\Instance;
use rock\snippets\filters\RateLimiterTrait;

/**
 * RateLimiter implements a rate limiting.
 *
 * You may use RateLimiter by attaching it as a behavior to a controller or module, like the following,
 *
 * ```php
 * public function behaviors()
 * {
 *     return [
 *         'rateLimiter' => [
 *             'class' => RateLimiter::className(),
 *             'actions' => [
 *                  'actionIndex' => [8, 60] // 8 iteration and 60 sec delay
 *             ]
 *         ],
 *     ];
 * }
 * ```
 *
 * When the user has exceeded his rate limit, RateLimiter will throw a {@see \rock\snippets\filters\RateLimiterException} exception.
 */
class RateLimiter extends ActionFilter
{
    use RateLimiterTrait;

    /**
     * Limit iterations.
     * @var int
     */
    public $defaultLimit = 8;
    /**
     * Delay (second).
     * @var int
     */
    public $defaultPeriod = 60;
    /**
     * List actions.
     * @var array
     */
    public $actions = [];

    public function init()
    {
        $this->session = Instance::ensure($this->session, '\rock\session\Session');
        $this->response = Instance::ensure($this->response, '\rock\response\Response');
        $this->actions = (array)$this->actions;
    }

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        if (empty($this->actions)) {
            return true;
        }

        if (isset($this->actions['*'])) {
            list ($limit, $period) = $this->actions['*'];
        } elseif (!empty($action) && isset($this->actions[$action])) {
            list ($limit, $period) = $this->actions[$action];
        } else {
            $limit = $this->defaultLimit;
            $period = $this->defaultPeriod;
        }

        if (!empty($action)) {
            $action =  '::' . $action;
        }
        return $this->check($limit, $period, get_class($this->owner) . $action);
    }
}