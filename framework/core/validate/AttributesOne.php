<?php

namespace rock\validate;


use rock\base\ObjectTrait;

class AttributesOne
{
    use ObjectTrait {
        ObjectTrait::__construct as parentConstruct;
    }

    public $attributes = [];
    public $valid = true;
    protected $errors = [];

    public function __construct($config = [])
    {
        $this->parentConstruct($config);
    }

    public function validate($value)
    {
        if (is_object($value)) {
            $value = (array)$value;
        }
        foreach ($this->attributes as $attribute => $validate) {
            if (!$validate instanceof Validate) {
                throw new ValidateException("`{$attribute}` is not `".Validate::className()."`");
            }
            if (!isset($value[$attribute])) {
                $value[$attribute] = null;
            }

            $validate->valid = $this->valid;
            if ($validate->validate($value[$attribute])) {
                continue;
            }

            if ($errors = $validate->getErrors()) {
                $this->errors[$attribute] = $errors;
                break;
            }
        }
    }

    public function getErrors()
    {
        return $this->errors;
    }
} 