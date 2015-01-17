<?php

namespace rockunit\core\widgets\mocks;


use rock\base\Model;

class ModelMock extends Model
{
    private $_attributes = [];
    public $rules = [];

    public function rules()
    {
        return $this->rules;
    }
    /**
     * Constructors.
     * @param array $attributes the dynamic attributes (name-value pairs, or names) being defined
     * @param array $config the configuration array to be applied to this object.
     */
    public function __construct(array $attributes = [], $config = [])
    {
        foreach ($attributes as $name => $value) {
            if (is_integer($name)) {
                $this->_attributes[$value] = null;
            } else {
                $this->_attributes[$name] = $value;
            }
        }
        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function __get($name)
    {
        if (array_key_exists($name, $this->_attributes)) {
            return $this->_attributes[$name];
        } else {
            return parent::__get($name);
        }
    }

    /**
     * @inheritdoc
     */
    public function __set($name, $value)
    {
        if (array_key_exists($name, $this->_attributes)) {
            $this->_attributes[$name] = $value;
        } else {
            parent::__set($name, $value);
        }
    }

    /**
     * @inheritdoc
     */
    public function __isset($name)
    {
        if (array_key_exists($name, $this->_attributes)) {
            return isset($this->_attributes[$name]);
        } else {
            return parent::__isset($name);
        }
    }

    /**
     * @inheritdoc
     */
    public function __unset($name)
    {
        if (array_key_exists($name, $this->_attributes)) {
            unset($this->_attributes[$name]);
        } else {
            parent::__unset($name);
        }
    }

    /**
     * Defines an attribute.
     * @param string $name the attribute name
     * @param mixed $value the attribute value
     */
    public function defineAttribute($name, $value = null)
    {
        $this->_attributes[$name] = $value;
    }

    /**
     * Undefines an attribute.
     * @param string $name the attribute name
     */
    public function undefineAttribute($name)
    {
        unset($this->_attributes[$name]);
    }

    /**
     * @inheritdoc
     */
    public function attributes()
    {
        return array_keys($this->_attributes);
    }
}