<?php

namespace rock\filters;

use rock\di\Container;
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
        if (!is_object($this->session)) {
            $this->session = Instance::ensure($this->session, '\rock\session\Session');
        }

        if (!is_object($this->response)) {
            $this->response = Container::load($this->response);
        }
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