<?php

namespace rock\components;


use rock\events\EventsInterface;

interface ComponentsInterface extends EventsInterface
{
    /**
     * Get data behaviors
     *
     * @return array
     */
    public function behaviors();

    /**
     * Returns the named behavior object.
     *
     * @param string $name the behavior name
     * @return Behavior the behavior object, or null if the behavior does not exist
     */
    public function getBehavior($name);

    public function existsBehavior($name);
    /**
     * Returns all behaviors attached to this component.
     *
     * @return Behavior[] list of behaviors attached to this component
     */
    public function getBehaviors();

    /**
     * Attaches a behavior to this component.
     *
     * This method will create the behavior object based on the given
     * configuration. After that, the behavior object will be attached to
     * this component by calling the {@see \rock\components\Behavior::attach()} method.
     * @param string $name the name of the behavior.
     * @param string|array|Behavior $behavior the behavior configuration. This can be one of the following:
     *
     *  - a {@see \rock\components\Behavior} object
     *  - a string specifying the behavior class
     *  - an object configuration array that will be passed to {@see \rock\di\Container::load()} to create the behavior object.
     *
     * @return Behavior the behavior object
     * @see detachBehavior()
     */
    public function attachBehavior($name, $behavior);

    /**
     * Attaches a list of behaviors to the component.
     *
     * Each behavior is indexed by its name and should be a {@see \rock\components\Behavior} object,
     * a string specifying the behavior class, or an configuration array for creating the behavior.
     * @param array $behaviors list of behaviors to be attached to the component
     * @see attachBehavior()
     */
    public function attachBehaviors(array $behaviors);


    /**
     * Makes sure that the behaviors declared in {@see \rock\components\ComponentsInterface::behaviors()} are attached to this component.
     */
    public function ensureBehaviors();

    /**
     * Detaches a behavior from the component.
     *
     * The behavior's {@see \rock\components\Behavior::detach()} method will be invoked.
     * @param string $name the behavior's name.
     * @return Behavior the detached behavior. Null if the behavior does not exist.
     */
    public function detachBehavior($name);

    /**
     * Detaches all behaviors from the component.
     */
    public function detachBehaviors();
} 