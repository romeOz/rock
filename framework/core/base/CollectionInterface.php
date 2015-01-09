<?php

namespace rock\base;


interface CollectionInterface extends \IteratorAggregate
{
    /**
     * @param string $name
     * @return mixed
     */
    public function get($name);

    /**
     * @param array $names
     * @return array
     */
    public function getMulti(array $names);

    /**
     * @param array $only  list of items whose value needs to be returned.
     * @param array $exclude list of items whose value should NOT be returned.
     * @return array
     */
    public function getAll(array $only = [], array $exclude = []);
    /**
     * @return int
     */
    public function getCount();

    /**
     * @param string $name
     * @param mixed $value
     */
    public function add($name, $value);

    /**
     * @param array $data
     */
    public function addMulti(array $data);

    /**
     * @param string $name
     * @return bool
     */
    public function exists($name);

    /**
     * @param string $name
     */
    public function remove($name);

    /**
     * @param array $names
     */
    public function removeMulti(array $names);

    public function removeAll();
}