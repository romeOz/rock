<?php
namespace rock\validation\rules;

use rock\validation\exceptions\ValidationException;


class OneOf extends AbstractComposite
{
    public function assert($input, $enableProvideError = true)
    {
        $validators = $this->getRules();
        $exceptions = $this->validateRules($input);
        $numRules = count($validators);
        $numExceptions = count($exceptions);
        $numPassed = $numRules - $numExceptions;
        if ($numExceptions === $numRules) {
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
        foreach ($this->getRules() as $v) {
            if ($v->validate($input)) {
                return true;
            }
        }

        return false;
    }

    public function check($input)
    {
        foreach ($this->getRules() as $v) {
            try {
                if ($v->setName($this->name)
                    ->setParams($this->params)
                    ->setModel($this->model)
                    ->setPlaceholders($this->placeholders)
                    ->setMessages($this->_messages)
                    ->check($input)) {
                    return true;
                }
            } catch (ValidationException $e) {
                if (!isset($firstException)) {
                    $firstException = $e;
                }
            }
        }

        if (isset($firstException)) {

            $this->provideErrorOne($firstException);
            throw $firstException;
        }

        return false;
    }
}

