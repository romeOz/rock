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
 *                  'actionIndex' => [100,10]
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

    public $actions = [];

    public function init()
    {
        $this->session = Instance::ensure($this->session, '\rock\session\Session');
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
            list ($limit, $period) = $this->actions['*'];
        } elseif (isset($this->actions[$action])) {
            list ($limit, $period) = $this->actions[$action];
        } else {
            return true;
        }

        return $this->check($limit, $period, get_class($this->owner) . '::' .$action);
    }
}