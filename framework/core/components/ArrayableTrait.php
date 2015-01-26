<?php
namespace rock\components;

use rock\helpers\ArrayHelper;
use rock\helpers\ObjectHelper;

/**
 * ArrayableTrait provides a common implementation of the {@see \rock\components\Arrayable} interface.
 *
 * ArrayableTrait implements {@see \rock\components\ArrayableTrait::toArray()} by respecting the field definitions as declared
 * in {@see \rock\components\ArrayableTrait::fields()} and {@see \rock\components\ArrayableTrait::extraFields()}.
 */
trait ArrayableTrait
{
    /**
     * Returns the list of fields that should be returned by default by {@see \rock\components\ArrayableTrait::toArray()} when no specific fields are specified.
     *
     * A field is a named element in the returned array by {@see \rock\components\ArrayableTrait::toArray()}.
     *
     * This method should return an array of field names or field definitions.
     * If the former, the field name will be treated as an object property name whose value will be used
     * as the field value. If the latter, the array key should be the field name while the array value should be
     * the corresponding field definition which can be either an object property name or a PHP callable
     * returning the corresponding field value. The signature of the callable should be:
     *
     * ```php
     * function ($field, $model) {
     *     // return field value
     * }
     * ```
     *
     * For example, the following code declares four fields:
     *
     * - `email`: the field name is the same as the property name `email`;
     * - `firstName` and `lastName`: the field names are `firstName` and `lastName`, and their
     *   values are obtained from the `first_name` and `last_name` properties;
     * - `fullName`: the field name is `fullName`. Its value is obtained by concatenating `first_name`
     *   and `last_name`.
     *
     * ```php
     * return [
     *     'email',
     *     'firstName' => 'first_name',
     *     'lastName' => 'last_name',
     *     'fullName' => function () {
     *         return $this->first_name . ' ' . $this->last_name;
     *     },
     * ];
     * ```
     *
     * In this method, you may also want to return different lists of fields based on some context
     * information. For example, depending on the privilege of the current application user,
     * you may return different sets of visible fields or filter out some fields.
     *
     * The default implementation of this method returns the public object member variables.
     *
     * @return array the list of field names or field definitions.
     * @see toArray()
     */
    public function fields()
    {
        $fields = array_keys(ObjectHelper::getObjectVars($this));

        return array_combine($fields, $fields);
    }

    /**
     * Returns the list of fields that can be expanded further and returned by {@see \rock\components\ArrayableTrait::toArray()}.
     *
     * This method is similar to {@see \rock\components\ArrayableTrait::fields()} except that the list of fields returned
     * by this method are not returned by default by {@see \rock\components\ArrayableTrait::toArray()}. Only when field names
     * to be expanded are explicitly specified when calling {@see \rock\components\ArrayableTrait::toArray()}, will their values
     * be exported.
     *
     * The default implementation returns an empty array.
     *
     * You may override this method to return a list of expandable fields based on some context information
     * (e.g. the current application user).
     *
     * @return array the list of expandable field names or field definitions. Please refer
     * to {@see \rock\components\ArrayableTrait::fields()} on the format of the return value.
     * @see toArray()
     * @see fields()
     */
    public function extraFields()
    {
        return [];
    }

    /**
     * Converts the model into an array.
     *
     * This method will first identify which fields to be included in the resulting array by calling {@see \rock\components\ArrayableTrait::resolveFields()}.
     * It will then turn the model into an array with these fields. If `$recursive` is true,
     * any embedded objects will also be converted into arrays.
     *
     * If the model implements the [[Linkable]] interface, the resulting array will also have a `_link` element
     * which refers to a list of links as specified by the interface.
     *
     * @param array $only        the fields being requested. If empty, all fields as specified
     *                           by {@see \rock\components\ArrayableTrait::fields()} will be returned.
     * @param array   $exclude
     * @param array   $expand    the additional fields being requested for exporting. Only fields declared
     *                           in {@see \rock\components\ArrayableTrait::extraFields()} will be considered.
     * @param boolean $recursive whether to recursively return array representation of embedded objects.
     * @return array the array representation of the object
     */
    public function toArray(array $only = [], array $exclude = [], array $expand = [], $recursive = true)
    {
        $data = [];
        foreach ($this->resolveFields($only, $exclude, $expand) as $field => $definition) {
            $data[$field] = is_string($definition) ? $this->$definition : call_user_func($definition, $field, $this);
        }

//        if ($this instanceof Linkable) {
//            $data['_links'] = Link::serialize($this->getLinks());
//        }

        return $recursive ? static::convert($data) : $data;
    }

    /**
     * Determines which fields can be returned by {@see \rock\components\ArrayableTrait::toArray()}.
     *
     * This method will check the requested fields against those declared in {@see \rock\components\ArrayableTrait::fields()}
     * and {@see \rock\components\ArrayableTrait::extraFields()} to determine which fields can be returned.
     *
     * @param array $only   the fields being requested for exporting
     * @param array $expand the additional fields being requested for exporting
     * @param array $exclude
     * @return array the list of fields to be exported. The array keys are the field names, and the array values
     *                      are the corresponding object property names or PHP callables returning the field values.
     */
    protected function resolveFields(array $only, array $exclude, array $expand)
    {
        $result = [];

        foreach ($this->fields() as $field => $definition) {
            if (is_integer($field)) {
                $field = $definition;
            }
            if (empty($only) || in_array($field, $only, true)) {
                $result[$field] = $definition;
            }

            if (!empty($exclude) && in_array($field, $exclude, true)) {
                unset($result[$field]);
            }
        }

        if (empty($expand)) {
            return $result;
        }

        foreach ($this->extraFields() as $field => $definition) {
            if (is_integer($field)) {
                $field = $definition;
            }
            if (in_array($field, $expand, true)) {
                $result[$field] = $definition;
            }
        }

        return $result;
    }

    /**
     * Converts an object or an array of objects into an array.
     *
     * @param object|array $object the object to be converted into an array
     * @param array $properties a mapping from object class names to the properties that need to put into the resulting arrays.
     * The properties specified for each class is an array of the following format:
     *
     * ```php
     * [
     *     'apps\models\Post' => [
     *         'id',
     *         'title',
     *         // the key name in array result => property name
     *         'createTime' => 'created_at',
     *         // the key name in array result => anonymous function
     *         'length' => function ($post) {
     *             return strlen($post->content);
     *         },
     *     ],
     * ]
     * ```
     *
     * The result of `Model::convert($post, $properties)` could be like the following:
     *
     * ```php
     * [
     *     'id' => 123,
     *     'title' => 'test',
     *     'createTime' => '2013-01-01 12:00AM',
     *     'length' => 301,
     * ]
     * ```
     *
     * @param boolean $recursive whether to recursively converts properties which are objects into arrays.
     * @return array the array representation of the object
     */
    public static function convert($object, $properties = [], $recursive = true)
    {
        if (is_array($object)) {
            if ($recursive) {
                foreach ($object as $key => $value) {
                    if (is_array($value) || is_object($value)) {
                        $object[$key] = static::convert($value, $properties, true);
                    }
                }
            }

            return $object;
        } elseif (is_object($object)) {
            if (!empty($properties)) {
                $className = get_class($object);
                if (!empty($properties[$className])) {
                    $result = [];
                    foreach ($properties[$className] as $key => $name) {
                        if (is_int($key)) {
                            $result[$name] = $object->$name;
                        } else {
                            $result[$key] = ArrayHelper::getValue($object, $name);
                        }
                    }

                    return $recursive ? static::convert($result) : $result;
                }
            }
            if ($object instanceof Arrayable) {
                $result = $object->toArray();
            } else {
                $result = [];
                foreach ($object as $key => $value) {
                    $result[$key] = $value;
                }
            }

            return $recursive ? static::convert($result) : $result;
        } else {
            return [$object];
        }
    }
}