<?php

namespace rock\helpers;

class Helper implements SerializeInterface
{
    public static function getValue(&$value, $default = null)
    {
        return $value ? : $default;
    }

    public static function getValueIsset(&$value, $default = null)
    {
        return isset($value) ? $value : $default;
    }


    public static function update(&$value, callable $callback, $default = null)
    {
        return $value ? $callback($value) : $default;
    }

    /**
     * Conversion to type
     *
     * @param mixed $value value
     * @return mixed
     */
    public static function toType($value)
    {
        if (!is_string($value)) {
            return $value;
        }
        if ($value === 'null') {
            $value = null;
        } elseif (is_numeric($value)) {
            $value = NumericHelper::toNumeric($value);
        } elseif ($value === 'false') {
            $value = false;
        } elseif ($value === 'true') {
            $value = true;
        }

        return $value;
    }

    public static function clearByType($value)
    {
        if (is_null($value)) {
            return null;
        }elseif (is_array($value)) {
            return [];
        } elseif (is_string($value)) {
            return null;
        } elseif (is_int($value) || is_float($value)) {
            return 0;
        } elseif (is_object($value) && !$value instanceof \Closure) {
            if (method_exists($value, 'reset')) {
                $value->reset();
                return $value;
            }
            $class = get_class($value);
            return new $class;
        }

        return $value;
    }
}