<?php

namespace rock\request;

use rock\base\BaseException;
use rock\base\CollectionInterface;
use rock\base\ObjectInterface;
use rock\base\ObjectTrait;
use rock\helpers\ArrayHelper;
use rock\helpers\Helper;

class RequestCollection implements \ArrayAccess, CollectionInterface, ObjectInterface
{
    use ObjectTrait;

    /** @var array */
    public $data = [];
    /** @var  string */
    public $method;


    /**
     * Returns an iterator for traversing the global vars in the collection.
     *
     * This method is required by the SPL interface `IteratorAggregate`.
     * It will be implicitly called when you use `foreach` to traverse the collection.
     *
     * @param array $only
     * @param array $exclude
     * @return \ArrayIterator an iterator for traversing the global vars in the collection.
     */
    public function getIterator(array $only = [], array $exclude = [])
    {
        return new \ArrayIterator($this->getAll($only, $exclude));
    }

    /**
     * Returns the number of global vars in the collection.
     * This method is required by the SPL `Countable` interface.
     * It will be implicitly called when you use `count($collection)`.
     *
     * @return integer the number of global vars in the collection.
     */
    public function count()
    {
        return $this->getCount();
    }

    /**
     * Returns the number of global vars in the collection.
     *
     * @return integer the number of global vars in the collection.
     */
    public function getCount()
    {
        return count($this->data);
    }

    /**
     * Removes all data resource.
     */
    public function removeAll()
    {
        $GLOBALS[$this->method] = $this->data = [];
    }


    /**
     * @inheritdoc
     */
    public function getAll(array $only = [], array $exclude = [])
    {
        return ArrayHelper::only($this->data, $only, $exclude);
    }

    /**
     * Returns whether there is a global var with the specified name.
     * This method is required by the SPL interface `ArrayAccess`.
     * It is implicitly called when you use something like `isset($collection[$name])`.
     *
     * @param string $name the global var name
     * @return boolean whether the named global var exists
     */
    public function offsetExists($name)
    {
        return $this->exists($name);
    }

    public function __isset($name)
    {
        return $this->exists($name);
    }


    /**
     * Returns whether there is a global var with the specified name.
     *
     * @param string $name the global var name
     * @return boolean whether the named global var exists
     */
    public function exists($name)
    {
        return isset($this->data[$name]);
    }

    /**
     * Returns the global var with the specified name.
     * This method is required by the SPL interface `ArrayAccess`.
     * It is implicitly called when you use something like `$global var = $collection[$name];`.
     * This is equivalent to [[get()]].
     *
     * @param string $name the global var name
     * @return mixed the global var with the specified name, null if the named global var does not exist.
     */
    public function offsetGet($name)
    {
        return $this->get($name);
    }

    /**
     * Returns the global var with the specified name.
     *
     * @param string $name the global var name
     * @return mixed the global var with the specified name. Null if the named global var does not exist.
     */
    public function get($name)
    {
        return Helper::getValueIsset($this->data[$name]);
    }

    /**
     *
     * @param string $name the resource name
     * @param mixed  $value
     * @throws BaseException
     */
    public function offsetSet($name, $value)
    {
        throw new RequestException(RequestException::UNKNOWN_METHOD, ['method' => __METHOD__]);
    }

    /**
     * Removes the named global var.
     * This method is required by the SPL interface `ArrayAccess`.
     *
     * @param string $name the global var name
     */
    public function offsetUnset($name)
    {
        $this->remove($name);
    }

    public function __unset($name)
    {
        $this->remove($name);
    }


    /**
     * Remove a data resource.
     *
     * @param $name
     */
    public function remove($name)
    {
        unset($GLOBALS[$this->method][$name], $this->data[$name]);
    }

    /**
     * Get data of resource (to object)
     *
     * @param string $name - key of array
     * @return mixed
     */
    public function __get($name)
    {
        return $this->get($name);
    }


    public function __set($name, $value)
    {
        throw new RequestException(RequestException::UNKNOWN_METHOD, ['method' => __METHOD__]);
    }

    /**
     * @param array $names
     * @return mixed
     */
    public function getMulti(array $names)
    {
        $result = [];
        foreach ($names as $name) {
            $result[$name] = $this->get($name);
        }

        return $result;
    }

    /**
     * @param string $name
     * @param mixed  $value
     * @throws RequestException
     */
    public function add($name, $value)
    {
        throw new RequestException(RequestException::UNKNOWN_METHOD, ['method' => __METHOD__]);
    }

    /**
     * @param array $data
     * @throws RequestException
     */
    public function addMulti(array $data)
    {
        throw new RequestException(RequestException::UNKNOWN_METHOD, ['method' => __METHOD__]);
    }

    /**
     * @param array $names
     */
    public function removeMulti(array $names)
    {
        foreach ($names as $name) {
            $this->remove($name);
        }
    }
}