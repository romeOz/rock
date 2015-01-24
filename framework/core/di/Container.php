<?php
namespace rock\di;

use rock\base\Alias;
use rock\base\ClassName;
use rock\helpers\ArrayHelper;
use rock\helpers\ObjectHelper;

class Container
{
    use ClassName;

    /**
     * Array of pointers to a single instance.
     *
     * @var array
     */
    protected static $instances = [];
    /**
     * Aliases of class.
     *
     * @var array
     */
    protected static $classAliases = [];
    /**
     * Names of class.
     *
     * @var array
     */
    protected static $classNames = [];

    /**
     * Creates a new object using the given configuration.
     *
     * The configuration can be either a string or an array.
     * If a string, it is treated as the *object class*; if an array,
     * it must contain a `class` element specifying the *object class*, and
     * the rest of the name-value pairs in the array will be used to initialize
     * the corresponding object properties.
     *
     * Below are some usage examples:
     *
     * ```php
     * $object = Container::load('\rock\db\Connection');
     * $object = Container::load(\rock\db\Connection::className());
     * $object = Container::load([
     *     'class' => '\rock\db\Connection',
     *     'dsn' => $dsn,
     *     'username' => $username,
     *     'password' => $password,
     * ]);
     * $object = Container::load($arg1, $arg2, [
     *     'class' => 'apps\frontend\FooController',
     *     'test' => 'test',
     * ]);
     * ```
     *
     *
     * This method can be used to create any object as long as the object's constructor is
     * defined like the following:
     *
     * ```php
     * public function __construct(..., $config = []) {
     * }
     * ```
     *
     * The method will pass the given configuration as the last parameter of the constructor,
     * and any additional parameters to this method will be passed as the rest of the constructor parameters.
     *
     * @internal param mixed $args arguments for constructor.
     * @param string|array $config         the configuration. It can be either a string representing the class name
     *                                     or an array representing the object configuration.
     * @param mixed        $throwException throws exception
     * @return null|object the created object
     * @throws ContainerException
     */
    public static function load(/*$args...*/$config, $throwException = true)
    {
        $args = func_get_args();
        list($config, $args) = static::calculateArgs($args);
        list($class, $config) = static::calculateConfig($config);

        if (!static::exists($class)) {
            if (!class_exists($class)) {
                if ($throwException) {
                    throw new ContainerException(ContainerException::UNKNOWN_CLASS, ['class' => $class]);
                }
                return null;
            }
            return static::newInstance($class, [$config], $args);
        }
        $data = static::_provide($class);
        // Lazy (single instance)
        if (static::isSingleton($class)) {
            $instance = static::getSingleton($data, $config, $args);

            return $instance;
        }
        $instance = static::getInstance($data, $config, $args);

        return $instance;
    }

    /**
     * Get config of class.
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
     * Get configs by classes.
     *
     * @param bool  $alias by alias
     * @param array $only  list of items whose value needs to be returned.
     * @param array $exclude list of items whose value should NOT be returned.
     * @return array the array representation of the collection.
     */
    public static function getAll(array $only = [], array $exclude = [], $alias = false)
    {
        return $alias === true
            ? ArrayHelper::only(static::$classAliases, $only, $exclude)
            : ArrayHelper::only(static::$classNames, $only, $exclude);
    }

    /**
     * Registry class.
     *
     * @param string $alias alias of class.
     * @param array|\Closure  $config
     * @throws ContainerException
     */
    public static function add($alias, $config)
    {
        if (is_array($config)) {
            if (!class_exists($config['class'])) {
                throw new ContainerException(ContainerException::UNKNOWN_CLASS, ['class' => $config['class']]);
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
            throw new ContainerException('Configuration must be an array or callable.');
        }

        unset(static::$instances[$alias]);
    }

    /**
     * Registry classes.
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
     * Exists class.
     *
     * @param string $name name/alias of class.
     * @return bool
     */
    public static function exists($name)
    {
        return !empty(static::$classNames[$name]) || !empty(static::$classAliases[$name]);
    }

    /**
     * Get count classes
     */
    public static function count()
    {
        return count(static::$classNames);
    }

    /**
     * Removes class and instance
     * @param string $name name/alias of class.
     */
    public static function remove($name)
    {
        unset(static::$classNames[$name], static::$classAliases[$name], static::$instances[$name]);
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
     * Removes all map classes and instances
     */
    public static function removeAll()
    {
        static::$classAliases = static::$classNames = static::$instances = [];
    }

    /**
     * Is single of class.
     *
     * @param string $name name/alias of class.
     * @return null
     */
    public static function isSingleton($name)
    {
        if (!static::exists($name)) {
            return false;
        }
        if (empty(static::$classNames[$name]['singleton']) &&
            empty(static::$classAliases[$name]['singleton'])
        ) {
            return false;
        }

        return true;
    }

    protected static function getSingleton(array $data, array $configs = [], array $args = [])
    {
        if (isset(static::$instances[$data['alias']])) {
            static::calculateArgsOfInstance($data['class'], $args);
            static::setProperty(static::$instances[$data['alias']], $data, $configs, $args);

            return static::$instances[$data['alias']];
        }
        if ($instance = self::getInstance($data, $configs, $args)) {
            //static::_callPermanentInit($instance, $data);
            return static::$instances[$data['alias']] = $instance;
        }

        return null;
    }

    protected static function setProperty($object, array $data, array $configs = [], array $args = [])
    {
        ObjectHelper::setProperties($object, !empty($configs) ? $configs : $data['properties']);
        call_user_func_array([$object, 'init'], $args);
    }

    protected static function getInstance(array $data, array $configs = [], array $args = [])
    {
        $class = $data['class'];
        // if is callable
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
            throw new ContainerException($e->getMessage(), [], $e);
        }
    }

    protected static function newInstance($class, array $configs = [], array $args = [])
    {
        $reflect = new \ReflectionClass($class);

        static::getReflectionArgs($reflect);
        static::calculateArgsOfInstance($reflect->getName(), $args, $configs);
        $args = array_merge($args, $configs);
        return $reflect->newInstanceArgs($reflect->getConstructor() ? $args : []);
    }

    protected static $args = [];

    protected static function getReflectionArgs(\ReflectionClass $reflect)
    {
        // lazy loading arguments
        if (isset(static::$args[$reflect->getName()])) {
            return static::$args[$reflect->getName()];
        }

        if ($params = $reflect->getConstructor()->getParameters())  {
            array_pop($params);
        }
        return static::$args[$reflect->getName()] = $params ? : [];
    }

    protected static function calculateArgsOfInstance($class, array &$args = [])
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
                    if (!static::exists($hint)) {
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

    protected static function calculateArgs(array $args)
    {
        $offset = is_bool(end($args)) ? -2 : -1;
        $configs = current(array_slice($args, $offset, 1)) ? : [];
        $args = array_slice($args, 0, count($args) + $offset);

        return [$configs, $args];
    }

    protected static function calculateConfig($config)
    {
        if (is_string($config)) {
            $class = Alias::getAlias($config);
            $config = [];
        } elseif (isset($config['class'])) {
            $class = Alias::getAlias($config['class']);
            unset($config['class'], $config['singleton']);
        } else {
            throw new ContainerException('Object configuration must be an array containing a "class" element.');
        }
        $class = ltrim(str_replace(['\\', '_', '/'], '\\', $class), '\\');

        return [$class, $config];
    }

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