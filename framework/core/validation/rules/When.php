<?php
namespace rock\validation\rules;

use rock\validation\Validatable;

class When extends AbstractRule
{
    public $when;
    public $then;
    public $else;

    public function __construct(Validatable $when, Validatable $then, Validatable $else)
    {
        $this->when = $when;
        $this->then = $then;
        $this->else = $else;
    }

    public function validate($input)
    {
        if (!empty($this->placeholders) || !empty($this->model)) {
            return $this->validateWithModelOrPlaceholder($input);
        }
        if ($this->when
            ->setName($this->name)
            ->setParams($this->params)
            ->setModel($this->model)
            ->setPlaceholders($this->placeholders)
            ->setMessages($this->_messages)
            ->validate($input)) {
            return $this->then
                ->setName($this->name)
                ->setParams($this->params)
                ->setModel($this->model)
                ->setPlaceholders($this->placeholders)
                ->setMessages($this->_messages)
                ->validate($input);
        } else {
            return $this->else
                ->setName($this->name)
                ->setParams($this->params)
                ->setModel($this->model)
                ->setPlaceholders($this->placeholders)
                ->setMessages($this->_messages)
                ->validate($input);
        }
    }

    public function assert($input)
    {
        if ($this->when
            ->setName($this->name)
            ->setParams($this->params)
            ->setModel($this->model)
            ->setPlaceholders($this->placeholders)
            ->setMessages($this->_messages)
            ->validate($input)) {
            return $this->then
                ->setName($this->name)
                ->setParams($this->params)
                ->setModel($this->model)
                ->setPlaceholders($this->placeholders)
                ->setMessages($this->_messages)
                ->assert($input);
        } else {
            return $this->else
                ->setName($this->name)
                ->setParams($this->params)
                ->setModel($this->model)
                ->setPlaceholders($this->placeholders)
                ->setMessages($this->_messages)
                ->assert($input);
        }
    }

    public function check($input)
    {
        if ($this->when
            ->setName($this->name)
            ->setParams($this->params)
            ->setModel($this->model)
            ->setPlaceholders($this->placeholders)
            ->setMessages($this->_messages)
            ->validate($input)) {
            return $this->then
                ->setName($this->name)
                ->setParams($this->params)
                ->setModel($this->model)
                ->setPlaceholders($this->placeholders)
                ->setMessages($this->_messages)
                ->check($input);
        } else {
            return $this->else
                ->setName($this->name)
                ->setParams($this->params)
                ->setModel($this->model)
                ->setPlaceholders($this->placeholders)
                ->setMessages($this->_messages)
                ->check($input);
        }
    }
}

