<?php

namespace rock\events;

/**
 * ActionEvent represents the event parameter used for an action event.
 *
 * By setting the {@see rock\events\ActionEvent::$isValid} property, one may control whether to continue running the action.
 */
class ActionEvent extends Event
{
    /**
     * @var string the action currently being executed
     */
    public $action;
    /**
     * @var mixed the action result. Event handlers may modify this property to change the action result.
     */
    public $result;
    /**
     * @var boolean whether to continue running the action. Event handlers of
     * {@see \rock\core\Controller::EVENT_BEFORE_ACTION} may set this property to decide whether
     * to continue running the current action.
     */
    public $isValid = true;


    /**
     * Constructor.
     *
     * @param string $action the action associated with this action event.
     * @param array $config name-value pairs that will be used to initialize the object properties
     */
    public function __construct($action, $config = [])
    {
        $this->action = $action;
        parent::__construct($config);
    }
} 