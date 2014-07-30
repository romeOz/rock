<?php
namespace rock\validation\rules;

class NoneOf extends AbstractComposite
{
    public function assert($input, $enableProvideError = true)
    {
        $exceptions = $this->validateRules($input);
        $numRules = count($this->getRules());
        $numExceptions = count($exceptions);
        if ($numRules !== $numExceptions) {
            $exception = $this->reportError($input)->setRelated($exceptions);
            if ($enableProvideError === true) {
                $this->provideErrorAll($exception);
            }

            throw $exception;
        }

        return true;
    }

    public function validate($input)
    {
        if (!empty($this->placeholders) || !empty($this->model)) {
            return $this->validateWithModelOrPlaceholder($input);
        }
        foreach ($this->getRules() as $rule) {
            if ($rule->validate($input)) {
                return false;
            }

        }
        return true;
    }
}

