<?php

namespace rock\base;


interface CollectionStaticInterface extends \IteratorAggregate, \Countable
{
    /**
     * @param string $name
     * @return mixed
     */
    public static function get($name);

    /**
     * @param array $names
     * @return mixed
     */
    public static function getMulti(array $names);

    /**
     * @param array $only  list of items whose value needs to be returned.
     * @param array $exclude list of items whose value should NOT be returned.
     * @return mixed
     */
    public static function getAll(array $only = [], array $exclude = []);

    /**
     * @return int
     */
    public static function getCount();

    /**
     * @param string $name
     * @param mixed $value
     */
    public static function add($name, $value);

    /**
     * @param array $data
     */
    public static function addMulti(array $data);

    /**
     * @param string $name
     * @return bool
     */
    public static function has($name);

    /**
     * @param string $name
     */
    public static function remove($name);

    /**
     * @param array $names
     */
    public static function removeMulti(array $names);

    public static function removeAll();
} 