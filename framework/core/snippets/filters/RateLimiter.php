<?php

namespace rock\snippets\filters;

use rock\helpers\Instance;

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
 *             'limit' => 10,
 *             'period' => 120
 *         ],
 *     ];
 * }
 * ```
 *
 * When the user has exceeded his rate limit, RateLimiter will throw a {@see \rock\filters\RateLimiterException} exception.
 */
class RateLimiter extends SnippetFilter
{
    use RateLimiterTrait;

    /**
     * Count of iteration.
     * @var int
     */
    public $limit = 5;
    /**
     * Period rate limit (second).
     * @var int
     */
    public $period = 180;

    public function init()
    {
        if (!is_object($this->response)) {
            $this->response = Instance::ensure($this->response, '\rock\response\Response', false);
        }

        if (!is_object($this->session)) {
            $this->session = Instance::ensure($this->session, '\rock\session\Session');
        }
    }

    /**
     * @inheritdoc
     */
    public function before()
    {
        return $this->check($this->limit, $this->period, get_class($this->owner));
    }
}