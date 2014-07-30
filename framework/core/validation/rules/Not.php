<?php
namespace rock\validation\rules;

use rock\validation\Validatable;
use rock\validation\exceptions\ValidationException;

class Not extends AbstractRule
{
    public $rule;

    public function __construct(Validatable $rule)
    {
        if ($rule instanceof AbstractComposite) {
            $rule = $this->absorbComposite($rule);
        }

        $this->rule = $rule;
    }

    public function validate($input)
    {
        if ($this->rule instanceof AbstractComposite) {
            return $this->rule
                ->setName($this->name)
                ->setParams($this->params)
                ->setModel($this->model)
                ->setPlaceholders($this->placeholders)
                ->setMessages($this->_messages)
                ->validate($input);
        }

        if (!empty($this->placeholders) || !empty($this->model)) {
            return $this->validateWithModelOrPlaceholder($input);
        }
        return!$this->rule
            ->setName($this->name)
            ->setParams($this->params)
            ->setModel($this->model)
            ->setPlaceholders($this->placeholders)
            ->setMessages($this->_messages)
            ->validate($input);
    }

    public function assert($input, $enableProvideError = true)
    {
        if ($this->rule instanceof AbstractComposite) {
            return $this->rule
                ->setName($this->name)
                ->setParams($this->params)
                ->setModel($this->model)
                ->setPlaceholders($this->placeholders)
                ->setMessages($this->_messages)
                ->assert($input, false);
        }

        try {
            $this->rule
                ->setName($this->name)
                ->setParams($this->params)
                ->setModel($this->model)
                ->setPlaceholders($this->placeholders)
                ->setMessages($this->_messages)
                ->assert($input, false);
        } catch (ValidationException $e) {
            return true;
        }

        $exception = $this->rule
            ->reportError($input)
            ->setMode(ValidationException::MODE_NEGATIVE);
        if ($enableProvideError === true) {
            $this->provideErrorOne($exception);
        }

        throw $exception;
    }

    protected function absorbComposite(AbstractComposite $rule)
    {
        $clone = clone $rule;
        $rules = $clone->getRules();
        $clone->removeRules();

        foreach ($rules as &$r) {
            if ($r instanceof AbstractComposite) {
                $clone->addRule($this->absorbComposite($r));
            } else {
                $clone->addRule(new static($r));
            }
        }

        return $clone;
    }
}

