<?php

namespace rock\filters;


use rock\base\Behavior;
use rock\base\ObjectTrait;

class ActionFilter extends Behavior
{
    public $only = [];
    public $when;

    public function before()
    {
        return true;
    }

    public function after()
    {
        return true;
    }


    protected function validateActions($action)
    {
        $only = $this->only;
        if (is_string($this->only)) {
            $only = [$this->only];
        }
        $only = array_flip($only);
        if (isset($only['*'])) {
            return true;
        }
        if (!empty($only) && isset($action) && !array_key_exists($action, $only)) {
            return false;
        }

        return true;
    }
} 