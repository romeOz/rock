<?php
namespace rock\validation\rules;

class AllOf extends AbstractComposite
{
    public function assert($input, $enableProvideError = true)
    {
        $exceptions = $this->validateRules($input);
        $numRules = count($this->rules);
        $numExceptions = count($exceptions);
        $summary = array(
            'total' => $numRules,
            'failed' => $numExceptions,
            'passed' => $numRules - $numExceptions
        );

        if (!empty($exceptions)) {
            $exception = $this->reportError($input, $summary)->setRelated($exceptions);
            if ($enableProvideError === true) {
                $this->provideErrorAll($exception);
            }

            throw $exception;
        }

        return true;
    }


    public function check($input)
    {
        foreach ($this->getRules() as $v) {
            if (!$v
                ->setName($this->name)
                ->setParams($this->params)
                ->setModel($this->model)
                ->setPlaceholders($this->placeholders)
                ->setMessages($this->_messages)
                ->check($input)) {
                return false;
            }
        }

        return true;
    }

    public function validate($input)
    {
        if (!empty($this->placeholders) || !empty($this->model)) {
            return $this->validateWithModelOrPlaceholder($input);
        }

        foreach ($this->getRules() as $v) {
            if (!$v
                ->setName($this->name)
                ->setParams($this->params)
                ->setModel($this->model)
                ->setPlaceholders($this->placeholders)
                ->setMessages($this->_messages)
                ->validate($input)) {
                return false;
            }
        }

        return true;
    }




}

