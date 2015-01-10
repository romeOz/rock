<?php

namespace rockunit\core\session\mocks;


use rock\db\DbSession;
use rock\helpers\ArrayHelper;

class DbSessionMock extends DbSession
{
    public function get($keys, $default = null)
    {
        $array = $this->readSession(session_id()) ? : [];
        if (is_string($array)) {
            $array = unserialize($array);
        }
        return ArrayHelper::getValue($array, $keys, $default);
    }


    public function add($keys, $value)
    {
        $array = $this->readSession(session_id()) ? : [];
        if (is_string($array)) {
            $array = unserialize($array);
        }
        ArrayHelper::setValue($array, $keys, $value);
        $this->writeSession(session_id(),serialize($array));
    }

    public function addMulti(array $data)
    {
        $array = $this->readSession(session_id()) ? : [];
        if (is_string($array)) {
            $array = unserialize($array);
        }
        $array = array_merge($array, $data);
        $this->writeSession(session_id(),serialize($array));
    }

    public function getAll(array $only = [], array $exclude = [])
    {
        $array = $this->readSession(session_id()) ? : [];
        if (is_string($array)) {
            $array = unserialize($array);
        }

        return ArrayHelper::only($array, $only, $exclude);
    }

    public function getCount()
    {
        $array = $this->readSession(session_id()) ? : [];

        return count($array);
    }

    public function remove($keys)
    {
        $array = $this->readSession(session_id()) ? : [];
        if (is_string($array)) {
            $array = unserialize($array);
        }

        $array = ArrayHelper::removeValue($array, $keys);
        $this->writeSession(session_id(),serialize($array));
    }

    public function removeAll()
    {
        $this->writeSession(session_id(),serialize([]));
    }

    public $timeout = 1440;
    public function getTimeout()
    {
        return $this->timeout;
    }

    public function setTimeout($value)
    {
        $this->timeout = (int)$value;
    }
}