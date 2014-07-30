<?php

namespace rock\filters;

use rock\validation\Validatable;

class ValidationFilters extends ActionFilter
{
    public $validation;

    public function after(&$result = null, $action = null)
    {
        if (!$this->validateActions($action)) {
            return true;
        }
        if ($this->validation instanceof \Closure) {
            return (bool)call_user_func($this->validation, $result);
        } elseif ($this->validation instanceof Validatable) {
            return $this->validation->validate($result);
        }

        return false;
    }
}