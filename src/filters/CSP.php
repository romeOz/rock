<?php

namespace rock\filters;


use rock\helpers\Instance;
use rock\response\Response;

/**
 * Content Security Policy filter.
 *
 * @link http://www.w3.org/TR/CSP/
 */
class CSP extends ActionFilter
{
    /**
     * @var Response the response to be sent. If not set, the `response` application component will be used.
     */
    public $response = 'response';
    public $policy = [];

    /**
     * @throws \rock\helpers\InstanceException
     */
    public function init()
    {
        $this->response = Instance::ensure($this->response, '\rock\response\Response');
    }
    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        $this->send();
        return true;
    }

    public function send()
    {
        $policy = [];
        foreach ($this->policy as $name => $value) {
            if (is_string($value)) {
                $value = str_replace('\'', '', $value);
                $value = preg_replace('/\b(self|none|unsafe-eval|unsafe-inline)\b/', "'$0'", $value);
                $policy[] = $name . ' ' . rtrim($value, ';') . ';';
                continue;
            }
            $value = implode(' ', $value);
            $value = str_replace('\'', '', $value);
            $value = preg_replace('/\b(self|none|unsafe-eval|unsafe-inline)\b/', "'$0'", $value);
            $policy[] = "{$name} {$value};";
        }

        if ($policy) {
            $policy = implode(' ', $policy);
            // @link http://caniuse.com/#feat=contentsecuritypolicy
            $this->response->getHeaders()
                ->set('Content-Security-Policy', $policy)
                ->set('X-Content-Security-Policy', $policy); // for IE10 or great
        }
    }
}