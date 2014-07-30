<?php
namespace rock\validation\rules;

use Traversable;
use rock\validation\Validatable;
use rock\validation\exceptions\ValidationException;

class Each extends AbstractRule
{
    public $itemValidator;
    public $keyValidator;

    public function __construct(Validatable $itemValidator = null,
                                Validatable $keyValidator=null)
    {
        $this->itemValidator = $itemValidator;
        $this->keyValidator = $keyValidator;
    }

    public function assert($input, $enableProvideError = true)
    {
        if (empty($input)) {
            return true;
        }

        $exceptions = array();

        if (!is_array($input) || $input instanceof Traversable) {
            $exception = $this->reportError($input);
            if ($enableProvideError === true) {
                $this->provideErrorOne($exception);
            }
            throw $exception;
        }

        foreach ($input as $key => $item) {
            if (isset($this->itemValidator)) {
                try {
                    $this->itemValidator->assert($item, false);
                } catch (ValidationException $e) {
                    $exceptions[] = $e;
                }
            }

            if (isset($this->keyValidator)) {
                try {
                    $this->keyValidator->assert($key, false);
                } catch (ValidationException $e) {
                    $exceptions[] = $e;
                }
            }
        }

        if (!empty($exceptions)) {
            $exception =$this->reportError($input)->setRelated($exceptions);
            if ($enableProvideError === true) {
                $this->provideErrorAll($exception);
            }
            throw $exception;
        }

        return true;
    }

    public function check($input)
    {
        if (empty($input)) {
            return true;
        }

        if (!is_array($input) || $input instanceof Traversable) {
            $exception = $this->reportError($input);
            $this->provideErrorOne($exception);

            throw $exception;
        }

        foreach ($input as $key => $item) {
            if (isset($this->itemValidator)) {
                $this->itemValidator->check($item);
            }

            if (isset($this->keyValidator)) {
                $this->keyValidator->check($key);
            }
        }

        return true;
    }

    public function validate($input)
    {
        if (!empty($this->placeholders) || !empty($this->model)) {
            return $this->validateWithModelOrPlaceholder($input);
        }

        if (!is_array($input) || $input instanceof Traversable) {
            return false;
        }

        if (empty($input)) {
            return true;
        }

        foreach ($input as $key => $item) {
            if (isset($this->itemValidator) &&
                !$this->itemValidator
                    ->setName($this->name)
                    ->setParams($this->params)
                    ->setModel($this->model)
                    ->setPlaceholders($this->placeholders)
                    ->setMessages($this->_messages)
                    ->validate($item)) {
                return false;
            }

            if (isset($this->keyValidator) &&
                !$this->keyValidator
                    ->setName($this->name)
                    ->setParams($this->params)
                    ->setModel($this->model)
                    ->setPlaceholders($this->placeholders)
                    ->setMessages($this->_messages)
                    ->validate($key)) {
                return false;
            }
        }

        return true;
    }
}

