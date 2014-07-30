<?php
namespace rock\validation\rules;

use rock\validation\exceptions\ValidationException;
use rock\validation\Validatable;
use rock\validation\Validation;

abstract class AbstractComposite extends AbstractRule
{

    protected $rules = array();

    public function __construct()
    {

        $this->addRules(func_get_args());
    }

    public function addRule($validator, $arguments=array())
    {
        if (!$validator instanceof Validatable) {
            $this->appendRule(Validation::buildRule($validator, $arguments));
        } else {
            $this->appendRule($validator);
        }

        return $this;
    }

    public function removeRules()
    {
        $this->rules = array();
    }

    public function addRules(array $validators)
    {
        foreach ($validators as $key => $spec) {
            if ($spec instanceof Validatable) {
                $this->appendRule($spec);
            } elseif (is_numeric($key) && is_array($spec)) {
                $this->addRules($spec);
            } elseif (is_array($spec)) {
                $this->addRule($key, $spec);
            } else {
                $this->addRule($spec);
            }
        }

        return $this;
    }

    /**
     * @return Validatable[]
     */
    public function getRules()
    {
        return $this->rules;
    }

    public function hasRule($validator)
    {
        if (empty($this->rules)) {
            return false;
        }

        if ($validator instanceof Validatable) {
            return isset($this->rules[spl_object_hash($validator)]);
        }

        if (is_string($validator)) {
            foreach ($this->rules as $rule) {
                if (get_class($rule) == __NAMESPACE__ . '\\' . $validator) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function appendRule(Validatable $validator)
    {
        $this->rules[spl_object_hash($validator)] = $validator;
    }

    protected function validateRules($input)
    {
        $validators = $this->getRules();
        $exceptions = array();
        foreach ($validators as $v) {
            try {
                $v->setName($this->name)
                    ->setParams($this->params)
                    ->setModel($this->model)
                    ->setPlaceholders($this->placeholders)
                    ->setMessages($this->_messages)
                    ->assert($input, false);
            } catch (ValidationException $e) {
                $exceptions[] = $e;
            }
        }

        return $exceptions;
    }
}

