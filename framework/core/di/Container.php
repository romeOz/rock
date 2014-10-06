<?php
namespace rock\di;

use rock\base\CollectionStaticInterface;
use rock\base\ObjectInterface;
use rock\base\ObjectTrait;
use rock\helpers\ArrayHelper;
use rock\helpers\ObjectHelper;
use rock\Rock;

class Container implements \ArrayAccess, CollectionStaticInterface, ObjectInterface
{
    use ObjectTrait;
    /**
     * Get instance.
     *
     * @param string|array $configs the configuration. It can be either a string representing the class name
     *                             or an array representing the object configuration.
     * @param mixed ...,$configs arguments for object.
     * @throws Exception
     * @return null|ObjectInterface
     */
    public static function load(/*$args...*/$configs)
    {
        $args = func_get_args();
        $configs = current(array_slice($args, -1, 1)) ? : [];
        $args = array_slice($args, 0, count($args)-1);
        list($class, $configs) = static::prepareConfig($configs);

        if (!static::has($class)) {
            if (!class_exists($class)) {
                throw new Exception(Exception::CRITICAL, Exception::UNKNOWN_CLASS, ['class' => $class]);
            }
            return static::newInstance($class, [$configs], $args);
        }
        $data = static::_provide($class);
        // Lazy (single instance)
        if (static::isSingleton($class)) {
            $instance = static::getSingleton($data, $configs, $args);

            return $instance;
        }
        $instance = static::getInstance($data, $configs, $args);

        return $instance;
    }

    /**
     * Exists dependency.
     *
     * @param string $name name/alias of class.
     * @return bool
     */
    public static function has($name)
    {
        return !empty(static::$classNames[$name]) || !empty(static::$classAliases[$name]);
    }

    /**
     * Is single of class.
     *
     * @param string $name name/alias of class.
     * @return null
     */
    public static function isSingleton($name)
    {
        if (!static::has($name)) {
            return false;
        }
        if (empty(static::$classNames[$name]['singleton']) &&
            empty(static::$classAliases[$name]['singleton'])
        ) {
            return false;
        }

        return true;
    }


    /**
     * @inheritdoc
     */
    public function getIterator(array $only = [], array $exclude = [],$alias = false)
    {
        return new \ArrayIterator($this->getAll($only, $exclude));
    }

    /**
     * @inheritdoc
     */
    public function count()
    {
        return static::getCount();
    }

    /**
     * @inheritdoc
     */
    public static function getCount()
    {
        return count(static::$classNames);
    }

    /**
     * @inheritdoc
     */
    public static function removeAll()
    {
        static::$classAliases = static::$classNames = static::$instances = [];
    }

    /**
     * Get data by classes.
     *
     * @param bool  $alias by alias
     * @param array $only  list of items whose value needs to be returned.
     * @param array $exclude list of items whose value should NOT be returned.
     * @return array the array representation of the collection.
     */
    public static function getAll(array $only = [], array $exclude = [], $alias = false)
    {
        return $alias === true
            ? ArrayHelper::prepareArray(static::$classAliases, $only, $exclude)
            : ArrayHelper::prepareArray(static::$classNames, $only, $exclude);
    }

    public function __isset($name)
    {
        return static::has($name);
    }


    /**
     * @inheritdoc
     */
    public function offsetExists($name)
    {
        return static::has($name);
    }

    /**
     * Get data of class.
     *
     * @param string $name name/alias of class.
     * @return null|array
     */
    public function offsetGet($name)
    {
        return static::get($name);
    }

    /**
     * Get data of class.
     *
     * @param string $name name/alias of class.
     * @return null|array
     */
    public static function get($name)
    {
        $name = ltrim($name, '\\');
        if (!$data = self::_provide($name)) {
            return null;
        }

        return $data;
    }

    /**
     * Registry dependency.
     *
     * @param string $alias alias of class.
     * @param array  $config
     */
    public function offsetSet($alias, $config)
    {
        static::add($alias, $config);
    }

    /**
     * Registry dependency.
     *
     * ```php
     * ['class_alias' => $params]
     * ```
     * @param array $dependencies
     */
    public static function addMulti(array $dependencies)
    {
        foreach ($dependencies as $alias => $config) {
            static::add($alias, $config);
        }
    }

    /**
     * Registry dependency.
     *
     * @param string $alias alias of class.
     * @param array|\Closure  $config
     * @throws Exception
     */
    public static function add($alias, $config)
    {
        if (is_array($config)) {
            if (!class_exists($config['class'])) {
                throw new Exception(Exception::CRITICAL, Exception::UNKNOWN_CLASS, ['class' => $config['class']]);
            }
            $name = $config['class'];
            $singleton = !empty($config['singleton']);
            unset($config['class'], $config['singleton']);
            static::$classNames[$name] = static::$classAliases[$alias] = [
                'singleton' => $singleton,
                'class' => $name,
                'alias' => $alias,
                'properties' => $config,
            ];
        } elseif ($config instanceof \Closure) {
            static::$classAliases[$alias] = ['class' => $config, 'alias' => $alias, 'properties' => []];
        } else {
            throw new Exception(Exception::CRITICAL, Exception::INVALID_CONFIG);
        }

        unset(static::$instances[$alias]);
    }


    /**
     * @param string $name name/alias of class.
     */
    public function offsetUnset($name)
    {
        static::remove($name);
    }

    public function __unset($name)
    {
        static::remove($name);
    }

    /**
     * @param string $name name/alias of class.
     */
    public static function remove($name)
    {
        unset(static::$classNames[$name], static::$classAliases[$name], static::$instances[$name]);
    }

    /**
     * Get data of class.
     *
     * @param string $name name/alias of class.
     * @return mixed
     */
    public function __get($name)
    {
        return static::get($name);
    }

    /**
     * Registry dependency.
     *
     * @param string $alias alias of class.
     * @param array $config
     */
    public function __set($alias, $config)
    {
        static::add($alias, $config);
    }

    /**
     * @param array $names names/aliases of classes.
     * @return mixed
     */
    public static function getMulti(array $names)
    {
        $result = [];
        foreach ($names as $name) {
            $result[$name] = static::get($name);
        }

        return $result;
    }

    /**
     * @param array $names names/aliases of classes.
     */
    public static function removeMulti(array $names)
    {
        foreach ($names as $name) {
            static::remove($name);
        }
    }

    /**
     * Array of pointers to a single instance.
     *
     * @var array
     */
    protected static $instances = [];
    /**
     * Aliases of class by dependencies.
     *
     * @var array
     */
    protected static $classAliases = [];
    /**
     * Names of class by dependencies.
     *
     * @var array
     */
    protected static $classNames = [];

    protected static function getSingleton(array $data, array $configs = [], array $args = [])
    {
        if (isset(static::$instances[$data['alias']])) {
            static::calculateArgs($data['class'], $args);
            static::setProperty(static::$instances[$data['alias']], $data, $configs, $args);

            return static::$instances[$data['alias']];
        }
        if ($instance = self::getInstance($data, $configs, $args)) {
            //static::_callPermanentInit($instance, $data);
            return static::$instances[$data['alias']] = $instance;
        }

        return null;
    }

    /**
     * Call SetProperty.
     *
     * @param ObjectInterface  $object
     * @param array $data
     * @param array $configs
     * @param array $args
     */
    protected static function setProperty($object, array $data, array $configs = [], array $args = [])
    {
//        if (!ObjectHelper::instanceOfTrait($object, ObjectTrait::className())) {
//            return;
//        }
        //$object->reset();
        ObjectHelper::setProperties($object, !empty($configs) ? $configs : $data['properties']);
        call_user_func_array([$object, 'init'], $args);
    }

    /**
     * Get instance.
     *
     * @param array $data array data of dependency.
     * @param array $configs
     * @param array $args array args of class.
     * @throws Exception
     * @return object
     */
    protected static function getInstance(array $data, array $configs = [], array $args = [])
    {
        $class = $data['class'];
        // if is closure
        if ($data['class'] instanceof \Closure) {
            return call_user_func(
                $data['class'],
                array_merge($args, $configs)
            );
        }
        try {
            return static::newInstance(
                $class,
                [array_merge($data['properties'], $configs)],//!empty($configs) ? [$configs] : [$data['properties']],
                $args
            );
        } catch (\Exception $e) {
            throw new Exception(Exception::CRITICAL, $e->getMessage(), [], $e);
        }
    }

    protected static function newInstance($class, array $configs = [], array $args = [])
    {
        $reflect = new \ReflectionClass($class);

        static::getReflectionArgs($reflect);
        static::calculateArgs($reflect->getName(), $args, $configs);
        $args = array_merge($args, $configs);
        return $reflect->newInstanceArgs($reflect->getConstructor() ? $args : []);
    }


    protected static $args = [];
    protected static function getReflectionArgs(\ReflectionClass $reflect)
    {
        if (isset(static::$args[$reflect->getName()])) {
            return static::$args[$reflect->getName()];
        }

        if ($params = $reflect->getConstructor()->getParameters())  {
            array_pop($params);
        }
        return static::$args[$reflect->getName()] = $params ? : [];
    }

    protected static function calculateArgs($class, array &$args = [])
    {
        if (empty(static::$args[$class])) {
            return;
        }
        $i = -1;
        /** @var \ReflectionParameter  $param */
        foreach (static::$args[$class] as $param) {
            ++$i;

            if ($param->getClass()) {
                $hint = $param->getClass()->getName();
                if (isset($args[$i]) && $args[$i] instanceof $hint) {
                    continue;
                }
                if ($param->isDefaultValueAvailable() && $param->getDefaultValue() === null) {
                    if (!static::has($hint)) {
                        if (!class_exists($hint)) {
                            $args[$i] = null;
                            continue;
                        }
                    }
                }
                $args[$i] = static::load(['class' => $hint]);
                continue;
            }

            if (isset($args[$i])) {
                continue;
            }
            if ($param->isDefaultValueAvailable()) {
                $args[$i] = $param->getDefaultValue();
            }

        }
    }

    protected static function prepareConfig($config)
    {
        if (is_string($config)) {
            $class = Rock::getAlias($config);
            $config = [];
        } elseif (isset($config['class'])) {
            $class = Rock::getAlias($config['class']);
            unset($config['class'], $config['singleton']);
        } else {
            throw new Exception(Exception::CRITICAL, Exception::ARGS_NOT_ARRAY);
        }
        $class = ltrim(str_replace(['\\', '_', '/'], '\\', $class), '\\');

        return [$class, $config];
    }

    /**
     * Get dependency.
     *
     * @param string $name name/alias of class.
     * @return null|array|\Closure
     */
    protected static function _provide($name)
    {
        if (!empty(static::$classNames[$name])) {
            return static::$classNames[$name];
        } elseif (!empty(static::$classAliases[$name])) {
            return static::$classAliases[$name];
        }

        return null;
    }
}