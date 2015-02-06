<?php

namespace rock\snippets\filters;


use rock\response\Response;
use rock\session\Session;

trait RateLimiterTrait
{
    /**
     * @var Session|string|array
     */
    public $session = 'session';
    /**
     * @var Response|string|array the response to be sent. If not set, the `response` application component will be used.
     */
    public $response = 'response';
    /**
     * @var boolean whether to include rate limit headers in the response
     */
    public $sendHeaders = true;

    public $throwException = false;
    /**
     * @var string the message to be displayed when rate limit exceeds
     */
    public $errorMessage = 'Rate limit exceeded.';
    /**
     * The condition which to run the {@see \rock\snippets\filters\RateLimiterTrait::saveAllowance()}.
     * @var callable|bool
     */
    public $dependency = true;

    /**
     * Checks whether the rate limit exceeds.
     *
     * @param int       $limit count of iteration
     * @param int       $period period rate limit (second)
     * @param string $name hash-key
     * @throws RateLimiterException
     * @return bool
     */
    public function check($limit, $period, $name)
    {
        $current = time();
        list ($maxRequests, $timestamp) = $this->loadAllowance($name);
        if ($maxRequests === null || ($current - $timestamp) >= $period) {
            $maxRequests = $limit;
        }
        if ($maxRequests < 1) {
            $this->saveAllowance($name, 0, $current);
            $this->addHeaders($this->response, $limit, 0, abs($current - $timestamp - $period));
            if ($this->throwException === true) {
                throw new RateLimiterException($this->errorMessage);
            }
            return false;
        }

        if ($this->dependency) {
            $this->saveAllowance($name, $maxRequests - 1, $current);
        }
        //$this->addHeaders($response, $limit, $maxRequests - 1, $period);

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
        if ($this->sendHeaders) {
            $response->getHeaders()
                ->set('X-Rate-Limit-Limit', $limit)
                ->set('X-Rate-Limit-Remaining', $remaining)
                ->set('X-Rate-Limit-Reset', $reset);
            $response->setStatusCode(429);
        }
    }

    /**
     * Loads the number of allowed requests and the corresponding timestamp from a persistent storage.
     *
     * @param string $name name of action e.g. `FooController::actionIndex`
     * @return array an array of two elements. The first element is the number of allowed requests,
     * and the second element is the corresponding UNIX timestamp.
     */
    public function loadAllowance($name)
    {
        if ((!$allowance = $this->session->get(['_allowance', $name]))) {
            return [null, time()];
        }

        return [$allowance['maxRequests'], $allowance['timestamp']];
    }

    /**
     * Saves the number of allowed requests and the corresponding timestamp to a persistent storage.
     *
     * @param string        $name name of action e.g. `FooController::actionIndex`
     * @param integer $maxRequests the number of allowed requests remaining.
     * @param integer $timestamp   the current timestamp.
     */
    public function saveAllowance($name, $maxRequests, $timestamp)
    {
        $this->session->add(['_allowance', $name], [
            'maxRequests' => $maxRequests,
            'timestamp' => $timestamp
        ]);
    }

    /**
     * Saves the number of allowed requests and the corresponding timestamp to a persistent storage.
     *
     * @param string $name name of action  e.g. `FooController::actionIndex`
     */
    public function removeAllowance($name)
    {
        $this->session->remove(['_allowance', $name]);
    }
}