<?php
namespace rock\helpers;

class BaseObjectHelper
{
    /**
     * Array of data optimization.
     *
     * @var array
     */
    protected static $caching = [];
    protected static $objects = [];
    protected static $staticProperties;

    /**
     * Set value.
     *
     * @param object $object
     * @param array  $keys
     * @param mixed  $value
     * @param bool   $throwException
     * @return object
     * @throws ObjectHelperException
     */
    public static function setValue($object, array $keys, $value = null, $throwException = false)
    {
        if (count($keys) > 1) {
            $property = array_shift($keys);
            if (!isset($object->$property) && $throwException === true) {
                throw new ObjectHelperException(
                    ObjectHelperException::CRITICAL, ObjectHelperException::SETTING_UNKNOWN_PROPERTY, [
                        'class' => get_class(
                            $object
                        ), 'property' => $property
                    ]
                );
            } else {
                $object->$property = new \stdClass();
            }
            $object->$property = static::setValue($object->$property, $keys, $value);
        } else {
            $property = array_shift($keys);
            if (!isset($object->$property) && $throwException === true) {
                throw new ObjectHelperException(
                    ObjectHelperException::CRITICAL, ObjectHelperException::SETTING_UNKNOWN_PROPERTY, [
                        'class' => get_class(
                            $object
                        ), 'property' => $property
                    ]
                );
            } else {
                $object->$property = new \stdClass();
            }
            $object->$property = $value;
        }

        return $object;
    }

    /**
     * Get method name.
     *
     * ```php
     * ObjectHelper::methodName('namespace\Foo', 'method');
     * // output: namespace\Foo::method
     * ```
     *
     * @param object|string $object
     * @param string        $method
     * @return string
     */
    public static function methodName($object, $method)
    {
        if (is_object($object)) {
            $object = get_class($object);
        }

        return "{$object}::{$method}";
    }

    /**
     * Convert multi-array in object.
     *
     * @param array $array
     * @param bool  $recursive
     * @return object|null
     */
    public static function toObject(array $array, $recursive = false)
    {
        if (empty($array)) {
            return null;
        }
        $hash = Helper::hash($array, Helper::SERIALIZE_JSON);
        if (isset(static::$objects[$hash])) {
            return static::$objects[$hash];
        }

        return static::$objects[$hash] = static::prepareToObject($array, $recursive);
    }

    protected static function prepareToObject(array $array, $recursive = false)
    {
        $object = new \stdClass();
        foreach ($array as $name => $value) {
            $name = trim($name);
            if (!strlen($name)) {
                continue;
            }
            $object->{$name} = is_array($value) && $recursive === true
                ? static::prepareToObject($value)
                : $value;
        }

        return $object;
    }

    /**
     * Returns the public member variables of an object.
     *
     * This method is provided such that we can get the public member variables of an object.
     * It is different from `get_object_vars()` because the latter will return private
     * and protected variables if it is called within the object itself.
     *
     * @param object $object the object to be handled
     * @return array the public member variables of the object
     */
    public static function getObjectVars($object)
    {
        return get_object_vars($object);
    }

    /**
     * Configures an object with the initial property values.
     *
     * @param object $object     the object to be configured
     * @param array  $properties the property initial values given in terms of name-value pairs.
     */
    public static function setProperties($object, array $properties)
    {
        $className = get_class($object);
        foreach ($properties as $name => $value) {
            if (isset($object::${$name})) {
                if (!isset(static::$staticProperties[$className][$name])) {
                    $className::${$name} = $value;
                    static::$staticProperties[$className][$name] = true;
                }
                continue;
            }
            $object->$name = $value;
        }
    }


    /**
     * @param array  $data
     * @param string $separator
     * @return array|\stdClass
     */
    public static function toMulti(array $data, $separator = '.')
    {
        $result = [];
        $toSubAssoc = function ($data, $separator) {
            $object = new \stdClass();
            foreach ($data as $key => $val) {
                $key = explode($separator, $key);
                if (count($key) === 1) {
                    $object->$key[0] = $val;
                } elseif (count($key) === 2) {
                    //$object->$key[0] = new \stdClass();
                    @$object->$key[0]->$key[1] = $val;
                } elseif (count($key) === 3) {
                    @$object->$key[0]->$key[1]->$key[2] = $val;
                } else {
                    $end_key = end($key);
                    unset($key[count($key) - 1]);
                    $buff = implode($separator, $key);
                    @$object->$buff->$end_key = $val;
                }
            }

            return $object;
        };
        if (ArrayHelper::depth($data) === 0) {
            $result = $toSubAssoc($data, $separator);
        } else {
            foreach ($data as $value) {
                $result[] = $toSubAssoc($value, $separator);
            }
        }

        return $result;
    }

    public static function basename($class)
    {
        $class = static::getClass($class);

        return String::basename($class);
    }

    /**
     * @param string|object $class
     * @return string
     */
    public static function getClass($class)
    {
        if (is_object($class)) {
            return get_class($class);
        }

        return ltrim(static::normalizeNamespace($class), '\\');
    }

    /**
     * Value to namespace.
     *
     * @param string $class value
     * @return string
     */
    public static function normalizeNamespace($class)
    {
        return preg_replace('/[\/_\\\]+/', '\\', $class);
    }

    public static function isNamespace($value)
    {
        return (bool)strstr($value, '\\');
    }


    /**
     * Get result method (dynamic args).
     *
     * @param object|string $object
     * @param string        $method_name name of method
     * @param array         $args        args of method
     * @return mixed
     */
    public static function call($object, $method_name, array $args = null)
    {
        $reflection = new \ReflectionMethod($object, $method_name);
        if (empty($args)) {
            return $reflection->invoke($object);
        }
        $pass = [];
        foreach ($reflection->getParameters() as $param) {
            /* @var $param \ReflectionParameter */
            if (isset($args[$param->getName()])) {
                $pass[] = $args[$param->getName()];
            } else {
                $pass[] = $param->getDefaultValue();
            }
        }

        return $reflection->invokeArgs($object, $pass);
    }

    public static function instanceOfTrait($class, $trait, $recursive = true)
    {
        return $recursive === true
            ? isset(ObjectHelper::getTraitsRecursive($class)[$trait])
            : isset(class_uses($class)[$trait]);
    }

    public static function getTraitsRecursive($class, $autoload = true)
    {
        $traits = [];
        do {
            $traits = array_merge(class_uses($class, $autoload), $traits);
        } while ($class = get_parent_class($class));
        foreach ($traits as $trait => $same) {
            $traits = array_merge(class_uses($trait, $autoload), $traits);
        }

        return array_unique($traits);
    }

    public static function calculateArgsConstructor($args)
    {
        $configs = current(array_slice($args, -1, 1));
        $args = array_slice($args, 0, count($args) - 1);
        if (!empty($configs) && !is_array($configs)) {
            $args = [$configs];
            $configs = [];
        }

        return [$configs ? $configs : [], $args];
    }
}