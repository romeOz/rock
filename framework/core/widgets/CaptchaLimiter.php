<?php

namespace rock\widgets;


use rock\snippets\filters\RateLimiter;

class CaptchaLimiter extends Captcha
{
    /**
     * @var bool
     */
    public $sendHeaders = false;
    /**
     * Count of iteration.
     * @var int
     */
    public $limit = 8;
    /**
     * Period rate limit (second).
     * @var int
     */
    public $period = 16;
    public $dependency = true;

    /**
     * Renders the widget.
     */
    public function run()
    {
        if ($this->check()) {
            echo '';
            return;
        }
        parent::run();
    }

    protected function check()
    {
        $dependency = $this->dependency;
        $action = get_class($this);
        if ($this->hasModel() && $this->activeField) {
            $dependency = isset($this->activeField->form->submitted) ? $this->activeField->form->submitted : $this->dependency;
            $action = get_class($this->model) . '::' . $this->attribute;
        }
        $config = [
            'sendHeaders' => $this->sendHeaders,
            'dependency' => $dependency
        ];
        return (new RateLimiter($config))->check($this->limit, $this->period, $action);
    }
} 