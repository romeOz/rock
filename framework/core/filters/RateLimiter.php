<?php

namespace rock\filters;

use rock\base\ActionFilter;
use rock\base\Controller;
use rock\request\Request;
use rock\response\Response;
use rock\Rock;
use rock\template\Template;
use rock\user\User;

/**
 * RateLimiter implements a rate limiting algorithm based on the [leaky bucket algorithm](http://en.wikipedia.org/wiki/Leaky_bucket).
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
 * When the user has exceeded his rate limit, RateLimiter will throw a {@see \rock\filters\RateLimiterException} exception.
 */
class RateLimiter extends ActionFilter
{
    public $actions = [];
    /**
     * @var boolean whether to include rate limit headers in the response
     */
    public $enableRateLimitHeaders = true;
    public $throwException = false;
    /**
     * @var string the message to be displayed when rate limit exceeds
     */
    public $errorMessage = 'Rate limit exceeded.';
    /**
     * @var User the user object that implements the RateLimitInterface.
     */
    public $user;
    /**
     * @var Request the current request. If not set, the `request` application component will be used.
     */
    public $request;
    /**
     * @var Response the response to be sent. If not set, the `response` application component will be used.
     */
    public $response;
    /**
     * The condition which to run the `\rock\user\User::saveAllowance`.
     * @var callable|bool
     */
    public $dependency = true;

    public function events()
    {
        return [
            Controller::EVENT_BEFORE_ACTION => 'beforeFilter',
            Controller::EVENT_AFTER_ACTION => 'afterFilter',
            Template::EVENT_BEFORE_TEMPLATE => 'beforeFilter',
            Template::EVENT_AFTER_TEMPLATE => 'afterFilter',
        ];
    }

    public function init()
    {
        if ($this->dependency instanceof \Closure) {
            $this->dependency = (bool)call_user_func($this->dependency, $this);
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

        return $this->check(
            $limit,
            $period,
            get_class($this->owner) . '::' .$action
        );

    }

    /**
     * Checks whether the rate limit exceeds.
     *
     * @param int       $limit
     * @param int       $period
     * @param string $action
     * @throws RateLimiterException
     * @return bool
     */
    public function check($limit, $period, $action)
    {
        $user = $this->user ? : Rock::$app->user;
        $response = $this->response ? : Rock::$app->response;
        $current = time();

        list ($maxRequests, $timestamp) = $user->loadAllowance($action);

        $maxRequests += (int) (($current - $timestamp) * $limit / $period);
        if ($maxRequests > $limit) {
            $maxRequests = $limit;
        }

        if ($maxRequests < 1) {
            $user->saveAllowance($action, 0, $current);
            $this->addHeaders($response, $limit, 0, $period);
            if ($this->throwException === true) {
                throw new RateLimiterException($this->errorMessage);
            }
            return false;
        }

        if ($this->dependency) {
            $user->saveAllowance($action, $maxRequests - 1, $current);
        }
        $this->addHeaders($response, $limit, $maxRequests - 1, (int) (($limit - $maxRequests) * $period / $limit));

        return true;
    }

    /**
     * Adds the rate limit headers to the response
     * @param Response $response
     * @param integer $limit the maximum number of allowed requests during a period
     * @param integer $remaining the remaining number of allowed requests within the current period
     * @param integer $reset the number of seconds to wait before having maximum number of allowed requests again
     */
    public function addHeaders($response, $limit, $remaining, $reset)
    {
        if ($this->enableRateLimitHeaders) {
            $response->getHeaders()
                ->set('X-Rate-Limit-Limit', $limit)
                ->set('X-Rate-Limit-Remaining', $remaining)
                ->set('X-Rate-Limit-Reset', $reset);
            $response->setStatusCode(429);
        }
    }
}