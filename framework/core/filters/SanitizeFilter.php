<?php

namespace rock\filters;


use rock\helpers\Sanitize;

class SanitizeFilter extends ActionFilter
{
    public $filters;

    public function after(&$result = null, $action = null)
    {
        if (!$this->validateActions($action)) {
            return true;
        }
        if (!isset($result)) {
            return true;
        }
        $result = Sanitize::sanitize($result, $this->filters);

        return true;
    }
}