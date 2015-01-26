<?php

namespace rock\components;


interface Arrayable
{
    /**
     * Returns the list of fields that should be returned by default by {@see \rock\components\Arrayable::toArray()} when no specific fields are specified.
     *
     * A field is a named element in the returned array by {@see \rock\components\Arrayable::toArray()}.
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
    public function fields();

    /**
     * Returns the list of fields that can be expanded further and returned by {@see \rock\components\Arrayable::toArray()}.
     *
     * This method is similar to {@see \rock\components\Arrayable::fields()} except that the list of fields returned
     * by this method are not returned by default by {@see \rock\components\Arrayable::toArray()}. Only when field names
     * to be expanded are explicitly specified when calling {@see \rock\components\Arrayable::toArray()}, will their values
     * be exported.
     *
     * The default implementation returns an empty array.
     *
     * You may override this method to return a list of expandable fields based on some context information
     * (e.g. the current application user).
     *
     * @return array the list of expandable field names or field definitions. Please refer
     * to {@see \rock\components\Arrayable::fields()} on the format of the return value.
     * @see toArray()
     * @see fields()
     */
    public function extraFields();
    /**
     * Returns the collection as a PHP array.
     *
     * @param array $only  list of items whose value needs to be returned.
     * @param array $exclude list of items whose value should NOT be returned.
     * @return array the array representation of the collection.
     */
    public function toArray(array $only = [], array $exclude = []);
}