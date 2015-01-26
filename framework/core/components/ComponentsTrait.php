<?php
namespace rock\components;

use rock\base\BaseException;
use rock\di\Container;
use rock\events\EventsTrait;

trait ComponentsTrait
{
    use EventsTrait;

    /** @var Behavior[]  */
    private $_behaviors;

    /**
     * Get data behaviors
     *
     * @return array
     */
    public function behaviors()
    {
        return [];
    }

    /**
     * Makes sure that the behaviors declared in {@see \rock\components\ComponentsInterface::behaviors()} are attached to this component.
     */
    public function ensureBehaviors()
    {
        if (!isset($this->_behaviors)) {
            $this->_behaviors = [];
            foreach ($this->behaviors() as $name => $behavior) {
                $this->_attachBehaviorInternal($name, $behavior);
            }
        }
    }

    /**
     * Attaches a behavior to this component.
     *
     * @param string|integer $name the name of the behavior. If this is an integer, it means the behavior
     * is an anonymous one. Otherwise, the behavior is a named one and any existing behavior with the same name
     * will be detached first.
     * @param string|array|Behavior $behavior the behavior to be attached
     * @return Behavior the attached behavior.
     */
    private function _attachBehaviorInternal($name, $behavior)
    {
        if (!($behavior instanceof Behavior)) {
            /** @var Behavior $behavior */
            $behavior = Container::load($behavior);
        }
        if (is_int($name)) {
            $behavior->attach($this);
            $this->_behaviors[] = $behavior;
        } else {
            if (isset($this->_behaviors[$name])) {
                $this->_behaviors[$name]->detach();
            }
            $behavior->attach($this);
            $this->_behaviors[$name] = $behavior;
        }
        return $behavior;
    }

    /**
     * Sets the value of a component property.
     *
     * This method will check in the following order and act accordingly:
     *
     *  - a property defined by a setter: set the property value
     *  - an event in the format of "on xyz": attach the handler to the event "xyz"
     *  - a behavior in the format of "as xyz": attach the behavior named as "xyz"
     *  - a property of a behavior: set the behavior property value
     *
     * Do not call this method directly as it is a PHP magic method that
     * will be implicitly called when executing `$component->property = $value;`.
     *
     * @param string $name the property name or the event name
     * @param mixed $value the property value
     * @throws BaseException if the property is not defined
     * @throws BaseException if the property is read-only.
     * @see __get()
     */
    public function __set($name, $value)
    {
        $setter = 'set' . $name;
        if (method_exists($this, $setter)) {
            // set property
            $this->$setter($value);

            return;
        } elseif (strncmp($name, 'on ', 3) === 0) {
            // on event: attach event handler
            $this->on(trim(substr($name, 3)), $value);

            return;
        } elseif (strncmp($name, 'as ', 3) === 0) {
            // as behavior: attach behavior
            $name = trim(substr($name, 3));
            $this->attachBehavior($name, $value instanceof Behavior ? $value : Container::load($value));

            return;
        } else {
            // behavior property
            $this->ensureBehaviors();
            /** @var Behavior $behavior */
            foreach ($this->_behaviors as $behavior) {
                if ($behavior->canSetProperty($name)) {
                    $behavior->$name = $value;

                    return;
                }
            }
        }
        if (method_exists($this, 'get' . $name)) {
            throw new BaseException(BaseException::SETTING_READ_ONLY_PROPERTY, [
                'class' => get_class(
                    $this
                ), 'property' => $name
            ]);
        } else {
            throw new BaseException(BaseException::SETTING_UNKNOWN_PROPERTY, [
                'class' => get_class($this), 'property' => $name
            ]);
        }
    }

    /**
     * Returns the value of a component property.
     *
     * This method will check in the following order and act accordingly:
     *
     *  - a property defined by a getter: return the getter result
     *  - a property of a behavior: return the behavior property value
     *
     * Do not call this method directly as it is a PHP magic method that
     * will be implicitly called when executing `$value = $component->property;`.
     *
     * @param string $name the property name
     * @return mixed the property value or the value of a behavior's property
     * @throws BaseException if the property is not defined
     * @throws BaseException if the property is write-only.
     * @see __set()
     */
    public function __get($name)
    {
        $getter = 'get' . $name;
        if (method_exists($this, $getter)) {
            // read property, e.g. getName()
            return $this->$getter();
        } else {
            // behavior property
            $this->ensureBehaviors();
            /** @var Behavior $behavior */
            foreach ($this->_behaviors as $behavior) {
                if ($behavior->canGetProperty($name)) {
                    return $behavior->$name;
                }
            }
        }
        if (method_exists($this, 'set' . $name)) {
            throw new BaseException(BaseException::GETTING_WRITE_ONLY_PROPERTY, [
                'class' => get_class(
                    $this
                ), 'property' => $name
            ]);
        } else {
            throw new BaseException(BaseException::GETTING_UNKNOWN_PROPERTY, [
                'class' => get_class($this), 'property' => $name
            ]);
        }
    }


    /**
     * Checks if a property value is null.
     *
     * This method will check in the following order and act accordingly:
     *
     *  - a property defined by a setter: return whether the property value is null
     *  - a property of a behavior: return whether the property value is null
     *
     * Do not call this method directly as it is a PHP magic method that
     * will be implicitly called when executing `isset($component->property)`.
     * @param string $name the property name or the event name
     * @return boolean whether the named property is null
     */
    public function __isset($name)
    {
        $getter = 'get' . $name;
        if (method_exists($this, $getter)) {
            return $this->$getter() !== null;
        } else {
            // behavior property
            $this->ensureBehaviors();
            /** @var Behavior $behavior */
            foreach ($this->_behaviors as $behavior) {
                if ($behavior->canGetProperty($name)) {
                    return $behavior->$name !== null;
                }
            }
        }

        return false;
    }

    /**
     * Sets a component property to be null.
     *
     * This method will check in the following order and act accordingly:
     *
     *  - a property defined by a setter: set the property value to be null
     *  - a property of a behavior: set the property value to be null
     *
     * Do not call this method directly as it is a PHP magic method that
     * will be implicitly called when executing `unset($component->property)`.
     *
     * @param string $name the property name
     * @throws BaseException if the property is read only.
     */
    public function __unset($name)
    {
        $setter = 'set' . $name;
        if (method_exists($this, $setter)) {
            $this->$setter(null);

            return;
        } else {
            // behavior property
            $this->ensureBehaviors();
            /** @var Behavior $behavior */
            foreach ($this->_behaviors as $behavior) {
                if ($behavior->canSetProperty($name)) {
                    $behavior->$name = null;

                    return;
                }
            }
        }
        throw new BaseException('Unsetting read-only property: ' . get_class($this) . '::' . $name);
    }

    /**
     * Calls the named method which is not a class method.
     *
     * This method will check if any attached behavior has
     * the named method and will execute it if available.
     *
     * Do not call this method directly as it is a PHP magic method that
     * will be implicitly called when an unknown method is being invoked.
     *
     * @param string $name the method name
     * @param array $params method parameters
     * @return mixed the method return value
     * @throws BaseException when calling unknown method
     */
    public function __call($name, $params)
    {
        $this->ensureBehaviors();
        /** @var Behavior $object */
        foreach ($this->_behaviors as $object) {
            if ($object->hasMethod($name)) {
                return call_user_func_array([$object, $name], $params);
            }
        }

        throw new BaseException(BaseException::UNKNOWN_METHOD, [
            'method' => get_class($this) . "::{$name}()"
        ]);
    }

    /**
     * This method is called after the object is created by cloning an existing one.
     * It removes all behaviors because they are attached to the old object.
     */
    public function __clone()
    {
        $this->_behaviors = [];
    }

    /**
     * Returns a value indicating whether a method is defined.
     *
     * A method is defined if:
     *
     * - the class has a method with the specified name
     * - an attached behavior has a method with the given name (when `$checkBehaviors` is true).
     *
     * @param string $name the property name
     * @param boolean $checkBehaviors whether to treat behaviors' methods as methods of this component
     * @return boolean whether the property is defined
     */
    public function hasMethod($name, $checkBehaviors = true)
    {
        if (method_exists($this, $name)) {
            return true;
        } elseif ($checkBehaviors) {
            $this->ensureBehaviors();
            /** @var Behavior $behavior */
            foreach ($this->_behaviors as $behavior) {
                if ($behavior->hasMethod($name)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Returns a value indicating whether a property is defined for this component.
     *
     * A property is defined if:
     *
     * - the class has a getter or setter method associated with the specified name
     *   (in this case, property name is case-insensitive);
     * - the class has a member variable with the specified name (when `$checkVars` is true);
     * - an attached behavior has a property of the given name (when `$checkBehaviors` is true).
     *
     * @param string $name the property name
     * @param boolean $checkVars whether to treat member variables as properties
     * @param boolean $checkBehaviors whether to treat behaviors' properties as properties of this component
     * @return boolean whether the property is defined
     * @see canGetProperty()
     * @see canSetProperty()
     */
    public function hasProperty($name, $checkVars = true, $checkBehaviors = true)
    {
        return $this->canGetProperty($name, $checkVars, $checkBehaviors) || $this->canSetProperty($name, false, $checkBehaviors);
    }

    /**
     * Returns a value indicating whether a property can be read.
     *
     * A property can be read if:
     *
     * - the class has a getter method associated with the specified name
     *   (in this case, property name is case-insensitive);
     * - the class has a member variable with the specified name (when `$checkVars` is true);
     * - an attached behavior has a readable property of the given name (when `$checkBehaviors` is true).
     *
     * @param string $name the property name
     * @param boolean $checkVars whether to treat member variables as properties
     * @param boolean $checkBehaviors whether to treat behaviors' properties as properties of this component
     * @return boolean whether the property can be read
     * @see canSetProperty()
     */
    public function canGetProperty($name, $checkVars = true, $checkBehaviors = true)
    {
        if (method_exists($this, 'get' . $name) || $checkVars && property_exists($this, $name)) {
            return true;
        } elseif ($checkBehaviors) {
            $this->ensureBehaviors();
            /** @var Behavior $behavior */
            foreach ($this->_behaviors as $behavior) {
                if ($behavior->canGetProperty($name, $checkVars)) {
                    return true;
                }
            }
        }

        return false;
    }
    /**
     * Returns a value indicating whether a property can be set.
     *
     * A property can be written if:
     *
     * - the class has a setter method associated with the specified name
     *   (in this case, property name is case-insensitive);
     * - the class has a member variable with the specified name (when `$checkVars` is true);
     * - an attached behavior has a writable property of the given name (when `$checkBehaviors` is true).
     *
     * @param string $name the property name
     * @param boolean $checkVars whether to treat member variables as properties
     * @param boolean $checkBehaviors whether to treat behaviors' properties as properties of this component
     * @return boolean whether the property can be written
     * @see canGetProperty()
     */
    public function canSetProperty($name, $checkVars = true, $checkBehaviors = true)
    {
        if (method_exists($this, 'set' . $name) || $checkVars && property_exists($this, $name)) {
            return true;
        } elseif ($checkBehaviors) {
            $this->ensureBehaviors();
            /** @var Behavior $behavior */
            foreach ($this->_behaviors as $behavior) {
                if ($behavior->canSetProperty($name, $checkVars)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Returns the named behavior object.
     * @param string $name the behavior name
     * @return Behavior the behavior object, or null if the behavior does not exist
     */
    public function getBehavior($name)
    {
        $this->ensureBehaviors();

        return isset($this->_behaviors[$name]) ? $this->_behaviors[$name] : null;
    }

    public function existsBehavior($name)
    {
        $this->ensureBehaviors();
        return !empty($this->_behaviors[$name]);
    }

    /**
     * Returns all behaviors attached to this component.
     * @return Behavior[] list of behaviors attached to this component
     */
    public function getBehaviors()
    {
        $this->ensureBehaviors();

        return $this->_behaviors;
    }

    /**
     * Attaches a list of behaviors to the component.
     *
     * Each behavior is indexed by its name and should be a {@see \rock\components\Behavior} object,
     * a string specifying the behavior class, or an configuration array for creating the behavior.
     * @param array $behaviors list of behaviors to be attached to the component
     * @see attachBehavior()
     */
    public function attachBehaviors(array $behaviors)
    {
        $this->ensureBehaviors();
        foreach ($behaviors as $name => $behavior) {
            $this->_attachBehaviorInternal($name, $behavior);
        }
    }

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
    public function attachBehavior($name, $behavior)
    {
        $this->ensureBehaviors();
        return $this->_attachBehaviorInternal($name, $behavior);
    }

    /**
     * Detaches a behavior from the component.
     *
     * The behavior's {@see \rock\components\Behavior::detach()} method will be invoked.
     * @param string $name the behavior's name.
     * @return Behavior the detached behavior. Null if the behavior does not exist.
     */
    public function detachBehavior($name)
    {
        $this->ensureBehaviors();
        if (isset($this->_behaviors[$name])) {
            $behavior = $this->_behaviors[$name];
            unset($this->_behaviors[$name]);
            $behavior->detach();

            return $behavior;
        } else {
            return null;
        }
    }

    /**
     * Detaches all behaviors from the component.
     */
    public function detachBehaviors()
    {
        $this->ensureBehaviors();
        foreach ($this->_behaviors as $name => $behavior) {
            $this->detachBehavior($name);
        }
    }
}
