<?php

namespace rock\rbac;


use rock\base\ObjectTrait;

abstract class Item implements ItemInterface
{
    use ObjectTrait;

    /**
     * @var string name of the rule
     */
    public $name;
    /**
     * @var mixed the additional data associated with this item
     */
    public $data;

    /**
     * @var string the item description
     */
    public $description;

    /**
     * Executes the rule.
     *
     * @param array $params - parameters passed to
     * @return boolean a value indicating whether the rule permits the auth item it is associated with.
     */
    public function execute(array $params = null)
    {
        return true;
    }
} 