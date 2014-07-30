<?php

namespace rock\base;


use rock\db\ActiveQueryInterface;
use rock\validation\Validation;

interface ComponentsInterface extends ObjectInterface
{
    /**
     * Get method
     *
     * @param string $name - name of method
     * @param array  $args - args action method
     * @return mixed
     * @throws \rock\exception\Exception
     */
    public function method($name, array $args = null);

    /**
     * Get data behaviors
     *
     * @return array
     */
    public function behaviors();

    /**
     * Add filters
     *
     * @param array $filters
     * @return static|ActiveQueryInterface
     */
    public function filters(array $filters);

    /**
     * Add validation
     *
     * @param \Closure|Validation $validation
     * @return static|ActiveQueryInterface
     */
    public function validation($validation);

    /**
     * Subscribing in event
     *
     * @param string $name - name of event
     * @return static|ActiveQueryInterface
     */
    public function trigger($name);

    /**
     * Publishing event
     *
     * @param string $name    - name of event
     * @param array|\Closure  $handler - handler
     * @return static|ActiveQueryInterface
     */
    public function on($name, $handler);

    /**
     * Detach event
     *
     * @param $name - name of event
     * @return static|ActiveQueryInterface
     */
    public function off($name);

    /**
     * Check Access
     *
     * @param array         $rules
     * @param array|\Closure|null $success
     * @param array|\Closure|null $fail
     * @return static|ActiveQueryInterface
     */
    public function checkAccess(array $rules, $success = null, $fail = null);

    /**
     * Returns the named behavior object.
     * @param string $name the behavior name
     * @return Behavior the behavior object, or null if the behavior does not exist
     */
    public function getBehavior($name);

    public function hasBehavior($name);
    /**
     * Returns all behaviors attached to this component.
     * @return Behavior[] list of behaviors attached to this component
     */
    public function getBehaviors();


    /**
     * Attaches a behavior to this component.
     * This method will create the behavior object based on the given
     * configuration. After that, the behavior object will be attached to
     * this component by calling the [[Behavior::attach()]] method.
     * @param string $name the name of the behavior.
     * @param string|array|Behavior $behavior the behavior configuration. This can be one of the following:
     *
     *  - a [[Behavior]] object
     *  - a string specifying the behavior class
     *  - an object configuration array that will be passed to [[Rock::factory()]] to create the behavior object.
     *
     * @return Behavior the behavior object
     * @see detachBehavior()
     */
    public function attachBehavior($name, $behavior);

    /**
     * Attaches a list of behaviors to the component.
     * Each behavior is indexed by its name and should be a [[Behavior]] object,
     * a string specifying the behavior class, or an configuration array for creating the behavior.
     * @param array $behaviors list of behaviors to be attached to the component
     * @see attachBehavior()
     */
    public function attachBehaviors(array $behaviors);


    /**
     * Makes sure that the behaviors declared in [[behaviors()]] are attached to this component.
     */
    public function ensureBehaviors();

    /**
     * Detaches a behavior from the component.
     * The behavior's [[Behavior::detach()]] method will be invoked.
     * @param string $name the behavior's name.
     * @return Behavior the detached behavior. Null if the behavior does not exist.
     */
    public function detachBehavior($name);

    /**
     * Detaches all behaviors from the component.
     */
    public function detachBehaviors();
} 