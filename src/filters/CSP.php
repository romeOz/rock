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
        if (!isset($this->policy['default-src'])) {
            $this->policy['default-src'] = "'self'";
        }
        foreach ($this->policy as $name => $value) {
            if (is_string($value)) {
                $value = str_replace('\'', '', $value);
                $value = preg_replace(['/\b(self|none|unsafe-eval|unsafe-inline)\b/', '/\s+/'], ["'$0'", ' '], $value);
                $policy[] = $name . ' ' . rtrim($value, ';') . ';';
                continue;
            }
            $value = implode(' ', $value);
            $value = str_replace('\'', '', $value);
            $value = preg_replace(['/\b(self|none|unsafe-eval|unsafe-inline)\b/', '/\s+/'], ["'$0'", ' '], $value);
            $policy[] = "{$name} {$value};";
        }

        if ($policy) {
            $policy = implode(' ', $policy);

            /* @link http://caniuse.com/#feat=contentsecuritypolicy
             * Does not conflict IE and Firefox <= 22 @link http://habrahabr.ru/company/yandex/blog/206508/ and @link https://events.yandex.ru/lib/talks/2587/
             */
            if ($this->isIE()) {
                $this->response->getHeaders()->set('X-Content-Security-Policy', $policy); // for IE10 or great (does not Edge)
                return;
            }
            $this->response->getHeaders()->set('Content-Security-Policy', $policy);
        }
    }

    /**
     * Check Internet Explorer (does not Edge).
     * @return bool
     */
    protected function isIE()
    {
        return isset($_SERVER['HTTP_USER_AGENT']) && (strpos($_SERVER['HTTP_USER_AGENT'], 'Trident') !== false || strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false);
    }
}