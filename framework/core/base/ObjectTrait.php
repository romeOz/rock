<?php

namespace rock\base;


use rock\exception\BaseException;
use rock\helpers\ArrayHelper;
use rock\helpers\Helper;
use rock\helpers\ObjectHelper;
use rock\Rock;
use rock\RockInterface;

trait ObjectTrait
{
    use ClassName;

    /**
     * Class "Rock"
     *
     * @var RockInterface
     */
    protected $Rock;

    /**
     * Array of data optimization
     *
     * @var array
     */
    protected static $caching = [];


    /**
     * Constructor.
     * The default implementation does two things:
     *
     * - Initializes the object with the given configuration `$config`.
     * - Call {@see \rock\base\ObjectInterface::init()}.
     *
     * If this method is overridden in a child class, it is recommended that
     *
     * - the last parameter of the constructor is a configuration array, like `$config` here.
     * - call the parent implementation at the end of the constructor.
     *
     * @param array $config
     */
    public function __construct($config = [])
    {
        $this->Rock = Rock::$app;
        $this->setProperties($config);
        $this->init();
    }

    /**
     * Initializes the object.
     * This method is invoked at the end of the constructor after the object is initialized with the
     * given configuration.
     */
    public function init()
    {
    }


    /**
     * Set properties
     *
     * @param array $config name-value pairs that will be used to initialize the object properties
     */
    public function setProperties(array $config = [])
    {
        if (!empty($config)) {
            ObjectHelper::setProperties($this, $config);
        }
    }

    public function mergeProperties(array $configs, $object)
    {
        foreach ($configs as $name) {
            if (property_exists($object, $name) &&
                property_exists($this, $name)
            ) {

                $this->$name = $object->$name;
            }
        }
    }

    /**
     * Returns the value of an object property.
     *
     * Do not call this method directly as it is a PHP magic method that
     * will be implicitly called when executing `$value = $object->property;`.
     *
     * @param string $name the property name
     * @return mixed the property value
     * @throws \Exception if the property is not defined
     * @see __set
     */
    public function __get($name)
    {
        $getter = 'get' . $name;
        if (method_exists($this, $getter)) {
            return $this->$getter();
        } elseif (method_exists($this, 'set' . $name)) {
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
     * Sets value of an object property.
     *
     * Do not call this method directly as it is a PHP magic method that
     * will be implicitly called when executing `$object->property = $value;`.
     *
     * @param string $name  the property name or the event name
     * @param mixed  $value the property value
     * @throws BaseException if the property is not defined
     * @throws BaseException if the property is read-only.
     * @see __get
     */
    public function __set($name, $value)
    {
        $setter = 'set' . $name;
        if (method_exists($this, $setter)) {
            $this->$setter($value);
        } elseif (method_exists($this, 'get' . $name)) {
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
     * Checks if the named property is set (not null).
     *
     * Do not call this method directly as it is a PHP magic method that
     * will be implicitly called when executing `isset($object->property)`.
     *
     * Note that if the property is not defined, false will be returned.
     *
     * @param string $name the property name or the event name
     * @return boolean whether the named property is set (not null).
     */
    public function __isset($name)
    {
        $getter = 'get' . $name;
        if (method_exists($this, $getter)) {
            return $this->$getter() !== null;
        } else {
            return false;
        }
    }

    /**
     * Sets an object property to null.
     *
     * Do not call this method directly as it is a PHP magic method that
     * will be implicitly called when executing `unset($object->property)`.
     *
     * Note that if the property is not defined, this method will do nothing.
     * If the property is read-only, it will throw an exception.
     *
     * @param string $name the property name
     * @throws BaseException if the property is read only.
     */
    public function __unset($name)
    {
        $setter = 'set' . $name;
        if (method_exists($this, $setter)) {
            $this->$setter(null);
        } elseif (method_exists($this, 'get' . $name)) {
            throw new BaseException('Unsetting read-only property: ' . get_class($this) . '::' . $name);
        }
    }

    /**
     * Calls the named method which is not a class method.
     *
     * Do not call this method directly as it is a PHP magic method that
     * will be implicitly called when an unknown method is being invoked.
     *
     * @param string $name   the method name
     * @param array  $params method parameters
     * @throws \Exception when calling unknown method
     * @return mixed the method return value
     */
    public function __call($name, $params = null)
    {
        throw new BaseException(BaseException::UNKNOWN_METHOD, [
            'method' => get_class($this) . "::{$name}()"
        ]);
    }

    /**
     * Returns a value indicating whether a property is defined.
     * A property is defined if:
     *
     * - the class has a getter or setter method associated with the specified name
     *   (in this case, property name is case-insensitive);
     * - the class has a member variable with the specified name (when `$checkVars` is true);
     *
     * @param string  $name      the property name
     * @param boolean $checkVars whether to treat member variables as properties
     * @return boolean whether the property is defined
     * @see canGetProperty
     * @see canSetProperty
     */
    public function hasProperty($name, $checkVars = true)
    {
        return $this->canGetProperty($name, $checkVars) || $this->canSetProperty($name, false);
    }


    /**
     * Returns a value indicating whether a property can be read.
     * A property is readable if:
     *
     * - the class has a getter method associated with the specified name
     *   (in this case, property name is case-insensitive);
     * - the class has a member variable with the specified name (when `$checkVars` is true);
     *
     * @param string  $name      the property name
     * @param boolean $checkVars whether to treat member variables as properties
     * @return boolean whether the property can be read
     * @see canSetProperty
     */
    public function canGetProperty($name, $checkVars = true)
    {
        return method_exists($this, 'get' . $name) || $checkVars && property_exists($this, $name);
    }

    /**
     * Returns a value indicating whether a property can be set.
     * A property is writable if:
     *
     * - the class has a setter method associated with the specified name
     *   (in this case, property name is case-insensitive);
     * - the class has a member variable with the specified name (when `$checkVars` is true);
     *
     * @param string  $name      the property name
     * @param boolean $checkVars whether to treat member variables as properties
     * @return boolean whether the property can be written
     * @see canGetProperty
     */
    public function canSetProperty($name, $checkVars = true)
    {
        return method_exists($this, 'set' . $name) || $checkVars && property_exists($this, $name);
    }

    /**
     * Returns a value indicating whether a method is defined.
     *
     * The default implementation is a call to php function `method_exists()`.
     * You may override this method when you implemented the php magic method `__call()`.
     *
     * @param string $name the property name
     * @return boolean whether the property is defined
     */
    public function hasMethod($name)
    {
        return method_exists($this, $name);
    }

    /**
     * Reset property without static property
     */
    public function reset()
    {
        $properties = array_intersect_key(
            (array)get_class_vars(get_class($this)),
            (array)get_object_vars($this)
        );
        foreach ($properties as $property => $value) {
            $this->{$property} = $value;
        }

        $this->Rock = Rock::$app;
    }

    public function resetStatic($name = null, array $keys = null)
    {
        $properties = array_diff_key(
            (array)get_class_vars(get_class($this)),
            (array)get_object_vars($this)
        );
        if (isset($name)) {
            if (isset($properties[$name])) {
                if (isset($keys)) {
                    static::${$name} = ArrayHelper::removeValue($properties[$name], $keys);
                    return;
                }
                static::${$name} = Helper::clearByType(static::${$name});
            }
            return;
        }
        foreach ($properties as $property => $value) {
            static::${$property} = Helper::clearByType($value);
        }
    }

    public function resetMultiStatic(array $names)
    {
        foreach ($names as $name) {
            $this->resetStatic($name);
        }
    }

    public function __sleep()
    {
        $this->Rock = null;
        return array_keys(get_object_vars($this));
    }

    public function __wakeup()
    {
        $this->Rock = Rock::$app;
    }
}