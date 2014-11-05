<?php
namespace rock\base;


use rock\event\Event;
use rock\helpers\Inflector;
use rock\Rock;
use rock\validate\Validate;

/**
 * Model is the base class for data models.
 *
 * Model implements the following commonly used features:
 *
 * - attribute declaration: by default, every public class member is considered as
 *   a model attribute
 * - attribute labels: each attribute may be associated with a label for display purpose
 * - massive attribute assignment
 * - scenario-based validation
 *
 * Model also raises the following events when performing data validation:
 *
 * - @see Model::EVENT_BEFORE_VALIDATE: an event raised at
 * the beginning of @see Model::validate()
 * - @see Model::EVENT_AFTER_VALIDATE : an event raised at
 * the end of @see Model::validate()
 *
 * You may directly use Model to store model data, or extend it with customization.
 *
 * @property array          $attributes  Attribute values (name => value).
 * @property array          $errors      An array of errors for all attributes. Empty array is returned if no error. The
 *          result is a two-dimensional array. See @see Model::getErrors() for detailed description. This property is read-only.
 * @property array          $firstErrors The first errors. An empty array will be returned if there is no error. This
 *          property is read-only.
 * @property \ArrayIterator $iterator    An iterator for traversing the items in the list. This property is
 *          read-only.
 * @property string         $scenario    The scenario that this model is in.
 * Defaults to @see Model::DEFAULT_SCENARIO .
 * @property \ArrayObject   $validators  All the validators declared in the model. This property is read-only.
 *
 * @package models
 */
class Model implements \IteratorAggregate, \ArrayAccess, Arrayable
{
    use ComponentsTrait {
        ComponentsTrait::__get as parentGet;
    }

    use ArrayableTrait;

//    const RULE_BEFORE_FILTERS = 'beforeFilters';
//    const RULE_AFTER_FILTERS = 'afterFilters';
//    const RULE_VALIDATION = 'validation';
//    const RULE_DEFAULT = 'default';
    const RULE_VALIDATE = 1;
    const RULE_SANITIZE = 2;

    /**
     * The name of the default scenario.
     */
    const DEFAULT_SCENARIO = 'default';

    /**
     * @event ModelEvent an event raised at the beginning
     * of @see validate() .
     */
    const EVENT_BEFORE_VALIDATE = 'beforeValidate';

    /**
     * @event Event an event raised at the end
     * of @see validate()
     */
    const EVENT_AFTER_VALIDATE = 'afterValidate';

    /**
     * @var string current scenario
     */
    protected $_scenario = self::DEFAULT_SCENARIO;

    /**
     * @var array validation errors (attribute name => array of errors)
     */
    protected $_errors = [];

    //protected $enableCsrfValidation = true;
    protected $useLabelsAsPlaceholders = true;

    /**
     * Returns the validation rules for attributes.
     *
     * Validation rules are used by @see validate() to check if attribute values are valid.
     * Child classes may override this method to declare different validation rules.
     *
     * Each rule is an array with the following structure:
     *
     * ```php
     * [
     *     'type',
     *     'handler',
     *     ['scenario1', 'scenario2']
     * ]
     * ```
     *
     * where
     *
     *  - attribute list: required, specifies the attributes array to be validated, for single attribute you can pass string;
     *  - validator type: required, specifies the validator to be used. It can be a built-in validator name,
     *    a method name of the model class, an anonymous function, or a validator class name.
     *  - on: optional, specifies the @see scenario array when the validation
     *    rule can be applied. If this option is not set, the rule will apply to all scenarios.
     *  - additional name-value pairs can be specified to initialize the corresponding validator properties.
     *    Please refer to individual validator class API for possible properties.
     *
     * A validator can be either an object of a class extending @see \rock\validate\Validate , or a model class method
     * (called *inline validator*) that has the following signature:
     *
     * ```php
     * // $params refers to validation parameters given in the rule
     * function validatorName($attribute, $params)
     * ```
     *
     * In the above `$attribute` refers to currently validated attribute name while `$params` contains an array of
     * validator configuration options such as `max` in case of `string` validator. Currently validate attribute value
     * can be accessed as `$this->[$attribute]`.
     *
     * Rock also provides a set of [[Validator::builtInValidators|built-in validators]].
     * They each has an alias name which can be used when specifying a validation rule.
     *
     * Below are some examples:
     *
     * ```php
     * [
     *   [ActiveRecord::RULE_FILTERS, [Sanitize::STRIP_TAGS, 'trim'], ['scenario1']]
     *   [ActiveRecord::RULE_VALIDATION, function(array $attributes){return Rock::$app->validation->notEmpty()->validate($attributes['name'], 'e_placeholder');}, ['scenario1']]
     *   [ActiveRecord::RULE_DEFAULT, ['time' => time(), 'password'=>function(array $attributes){return md5($attributes['password']);}]]
     * ];
     * ```
     *
     * Note, in order to inherit rules defined in the parent class, a child class needs to
     * merge the parent rules with child rules using functions such as `array_merge()`.
     *
     * @return array validation rules
     */
    public function rules()
    {
        return [];
    }

    /**
     * Sets the scenario for the model.
     * Note that this method does not check if the scenario exists or not.
     * The method [[validate()]] will perform this check.
     * @param string $value the scenario that this model is in.
     */
    public function setScenario($value)
    {
        $this->_scenario = $value;
    }

    /**
     * Returns the form name that this model class should use.
     *
     * The form name is mainly used by `\rock\widgets\ActiveForm` to determine how to name
     * the input fields for the attributes in a model. If the form name is "A" and an attribute
     * name is "b", then the corresponding input name would be "A[b]". If the form name is
     * an empty string, then the input name would be "b".
     *
     * By default, this method returns the model class name (without the namespace part)
     * as the form name. You may override it when the model is used in different forms.
     *
     * @return string the form name of this model class.
     */
    public function formName()
    {
        $reflector = new \ReflectionClass($this);

        return $reflector->getShortName();
    }

    protected $_attributeNames;
    /**
     * Returns the list of attribute names.
     * By default, this method returns all public non-static properties of the class.
     * You may override this method to change the default behavior.
     *
     * @return array list of attribute names.
     */
    public function attributes()
    {
        if (isset($this->_attributeNames)) {
            return $this->_attributeNames;
        }
        $class = new \ReflectionClass($this);
        $names = [];
        foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $name = $property->getName();
            if (!$property->isStatic()) {
                $names[] = $name;
            }
        }

        return $this->_attributeNames = $names;
    }

    /**
     * Returns the attribute labels.
     *
     * Attribute labels are mainly used for display purpose. For example, given an attribute
     * `firstName`, we can declare a label `First Name` which is more user-friendly and can
     * be displayed to end users.
     *
     * By default an attribute label is generated using [[generateAttributeLabel()]].
     * This method allows you to explicitly specify attribute labels.
     *
     * Note, in order to inherit labels defined in the parent class, a child class needs to
     * merge the parent labels with child labels using functions such as `array_merge()`.
     *
     * @return array attribute labels (name => label)
     * @see generateAttributeLabel
     */
    public function attributeLabels()
    {
        return [];
    }

    /**
     * Performs the data validation.
     *
     * This method executes the validation rules applicable to the current @see scenario.
     * The following criteria are used to determine whether a rule is currently applicable:
     *
     * - the rule must be associated with the attributes relevant to the current scenario;
     * - the rules must be effective for the current scenario.
     *
     * This method will call @see beforeValidate()
     * and @see afterValidate() before and
     * after the actual validation, respectively. If @see beforeValidate() returns false,
     * the validation will be cancelled and @see afterValidate() will not be called.
     *
     * Errors found during the validation
     * can be retrieved via @see getErrors() ,
     *
     * @see getFirstErrors()
     * and @see getFirstError() .
     *
     * @param array $attributes list of attributes that should be validated.
     *                          If this parameter is empty, it means any attribute listed in the applicable
     *                          validation rules should be validated.
     * @param boolean $clearErrors whether to
     * call @see clearErrors() before performing validation
     * @return boolean whether the validation is successful without any error.
     */
    public function validate(array $attributes = null, $clearErrors = true)
    {
        $rules = $this->rules();
        if (empty($rules)) {
            return true;
        }

        if ($clearErrors) {
            $this->clearErrors();
        }
        if ($attributes === null) {
            $attributes = $this->getAttributes();
        }

        if (!$this->beforeValidate()) {
            return false;
        }
        if ($this->_rulesInternal($attributes, $rules) === false) {
            Event::offClass($this);
            //$this->detachEvents();
            return false;
        }
        $this->afterValidate();
        return true;
    }

    private function _rulesInternal(array $attributes, array $rules)
    {
        foreach($rules as $rule){
            if (isset($rule['scenarios'])) {
                if (is_string($rule['scenarios']) && $this->scenario !== $rule['scenarios']) {
                    continue;
                }
                if (is_array($rule['scenarios']) && !in_array($this->scenario, $rule['scenarios'], true)) {
                    continue;
                }
                unset($rule['scenarios']);
            }

            $type = array_shift($rule);
            if ($type !== self::RULE_SANITIZE && $type !== self::RULE_VALIDATE) {
                throw new ModelException(ModelException::ERROR, "Unknown type of rule: {$type}");
            }
            $attributeNames = array_shift($rule);
            if (is_string($attributeNames)) {
                $attributeNames = [$attributeNames];
            }
            if ($type === self::RULE_SANITIZE) {
                $attributes = $this->_filterInternal($attributeNames, $attributes, $rule);
                if (!$this->hasErrors()) {
                    $this->setAttributes($attributes);
                }
                continue;
            }

            if ($type === self::RULE_VALIDATE) {
                if (!$this->_validateInternal($attributeNames, $attributes, $rule)) {
                    break;
                }
                continue;
            }
        }

        if ($this->hasErrors()) {
            return false;
        }
        $this->setAttributes($attributes);
        return true;
    }

    private function _filterInternal(array $attributeNames, array $attributes, array $rules)
    {
        foreach ($attributeNames as $name) {
            if (!isset($attributes[$name])) {
                $attributes[$name] = null;
            }

            foreach ($rules as $key => $rule) {
                $args = [];
                if (is_string($key)) {
                    if (!is_array($rule)) {
                        throw new ModelException(ModelException::ERROR, 'Arguments must be `array`');
                    }
                    $args = $rule;
                    $rule = $key;
                }
                // closure
                if ($rule instanceof \Closure) {
                    array_unshift($args, $attributes[$name]);
                    $attributes[$name] = call_user_func_array($rule, $args);
                    continue;
                }

                // method
                if (method_exists($this, $rule)) {
                    array_unshift($args, $attributes[$name]);
                    $attributes[$name] = call_user_func_array([$this, $rule], $args);
                    continue;
                }

                /** @var \rock\sanitize\Sanitize $sanitize */
                $sanitize = Rock::factory(\rock\sanitize\Sanitize::className());
                // function
                if (function_exists($rule) && !$sanitize->existsRule($rule)) {
                    array_unshift($args, $attributes[$name]);
                    $attributes[$name] = call_user_func_array($rule, $args);
                    continue;
                }

                $attributes[$name] = call_user_func_array([$sanitize, $rule], $args)->sanitize($attributes[$name]);
            }
        }
        return $attributes;
    }

    private function _validateInternal(array $attributeNames, array $attributes, array $rules)
    {
        $messages = [];
        $valid = true;
        if (isset($rules['messages'])) {
            $messages = $rules['messages'];
        }
        foreach ($attributeNames as $name) {
            if (!isset($attributes[$name])) {
                $attributes[$name] = null;
            }
            $placeholders = [];
            if (isset($rules['placeholders'])) {
                $placeholders = $rules['placeholders'];
            }
            if ($this->useLabelsAsPlaceholders && !isset($placeholders['name'])) {
                if (($label = $this->attributeLabels()) && isset($label[$name])) {
                    $placeholders['name'] = $label[$name];
                }
            }
            foreach ($rules as $key => $ruleName) {
                if ($key === 'placeholders' || $key === 'messages' || $key === 'one' || $key === 'when') {
                    continue;
                }
                if ($ruleName === 'one') {
                    $rules[$ruleName] = 0;
                    continue;
                }
                $args = [];
                if (is_string($key)) {
                    if (!is_array($ruleName)) {
                        throw new ModelException(ModelException::ERROR, 'Arguments must be `array`');
                    }
                    $args = $ruleName;
                    $ruleName = $key;
                }

                // closure
                if ($ruleName instanceof \Closure) {
//                    if ($attributes[$name] === '') {
//                        continue;
//                    }
                    array_unshift($args, $this);
                    array_unshift($args, $name);
                    array_unshift($args, $attributes[$name]);
                    if (!call_user_func_array($ruleName, $args)) {
                        $valid = false;
                    }
                    continue;
                }

                // method
                if (method_exists($this, $ruleName) || $ruleName instanceof \Closure) {
//                    if ($attributes[$name] === '') {
//                        continue;
//                    }
                    array_unshift($args, $this);
                    array_unshift($args, $name);
                    array_unshift($args, $attributes[$name]);
                    $fx = method_exists($this, $ruleName) ? [$this, $ruleName] : $ruleName;
                    if (!call_user_func_array($fx, $args)) {
                        $valid = false;
                    }
                    continue;
                }

                /** @var Validate $validate */
                $validate = Rock::factory(Validate::className());
                // function
                if (function_exists($ruleName) && !$validate->existsRule($ruleName)) {
                    if ($attributes[$name] === '') {
                        continue;
                    }
                    array_unshift($args, $attributes[$name]);
                    if (!call_user_func_array($ruleName, $args)) {
                        if (!isset($placeholders['name'])) {
                            $placeholders['name'] = 'value';
                        }
                        $message = isset($messages[$ruleName]) ? $messages[$ruleName] : Rock::t('validation.call', $placeholders);
                        $this->addError($name, $message);
                        $valid = false;
                    }
                    continue;
                }

                // rule
                /** @var Validate $validate */
                $validate = call_user_func_array([$validate, $ruleName], $args);
                if ($placeholders) {
                    $validate->placeholders($placeholders);
                }
                if ($messages) {
                    $validate->messages($messages);
                }
                if (!$validate->validate($attributes[$name])) {
                    $valid = false;
                    $this->addError($name, $validate->getFirstError());
                }

            }
            if (isset($rules['one'])) {
                if ((is_int($rules['one']) || $rules['one'] === $name) && !$valid) {
                    return false;
                }
            }
        }

        if (isset($rules['when']) && $valid === true) {
            return $this->_validateInternal($attributeNames, $attributes, $rules['when']);
        }
        return true;
    }

//    protected $_activeAttributeName;
//
//    public function getActiveAttributeName()
//    {
//        return $this->_activeAttributeName;
//    }
//
//    public function setActiveAttributeName($attribute)
//    {
//        $this->_activeAttributeName = $attribute;
//    }


    /**
     * Validates multiple models.
     * This method will validate every model. The models being validated may
     * be of the same or different types.
     * @param array $models the models to be validated
     * @param array $attributeNames list of attribute names that should be validated.
     * If this parameter is empty, it means any attribute listed in the applicable
     * validation rules should be validated.
     * @return boolean whether all models are valid. False will be returned if one
     * or multiple models have validation error.
     */
    public static function validateMultiple($models, $attributeNames = null)
    {
        $valid = true;
        /** @var Model $model */
        foreach ($models as $model) {
            $valid = $model->validate($attributeNames) && $valid;
        }

        return $valid;
    }


    /**
     * This method is invoked before validation starts.
     *
     * The default implementation raises a {@see \rock\base\Model::beforeValidate()} event.
     * You may override this method to do preliminary checks before validation.
     * Make sure the parent implementation is invoked so that the event can be raised.
     * @return boolean whether the validation should be executed. Defaults to true.
     * If false is returned, the validation will stop and the model is considered invalid.
     */
    public function beforeValidate()
    {
        $event = new ModelEvent;
        $this->trigger(self::EVENT_BEFORE_VALIDATE, $event);
        return $event->isValid;
    }

    /**
     * This method is invoked after validation ends.
     *
     * The default implementation raises an {@see \rock\base\Model::afterValidate()} event.
     * You may override this method to do postprocessing after validation.
     * Make sure the parent implementation is invoked so that the event can be raised.
     */
    public function afterValidate()
    {
        $this->trigger(self::EVENT_AFTER_VALIDATE);
    }



    /**
     * Returns a value indicating whether the attribute is safe for massive assignments.
     * @param string $attribute attribute name
     * @return boolean whether the attribute is safe for massive assignments
     */
    public function isAttributeSafe($attribute)
    {
        return in_array($attribute, $this->safeAttributes(), true);
    }
    /**
     * Returns the text label for the specified attribute.
     *
     * @param string $attribute the attribute name
     * @return string the attribute label
     * @see generateAttributeLabel
     * @see attributeLabels
     */
    public function getAttributeLabel($attribute)
    {
        $labels = $this->attributeLabels();

        return isset($labels[$attribute])
            ? $labels[$attribute]
            : $this->generateAttributeLabel($attribute);
    }

    /**
     * Returns a value indicating whether there is any validation error.
     *
     * @param string|null $attribute attribute name. Use null to check all attributes.
     * @return boolean whether there is any error.
     */
    public function hasErrors($attribute = null)
    {
        return $attribute === null
            ? !empty($this->_errors)
            : isset($this->_errors[$attribute]);
    }

    /**
     * Returns the errors for all attribute or a single attribute.
     *
     * @param string   $attribute attribute name. Use null to retrieve errors for all attributes.
     * @property array An         array of errors for all attributes. Empty array is returned if no error.
     *                            The result is a two-dimensional array. See @see getErrors() for detailed description.
     * @return array errors for all attributes or the specified attribute. Empty array is returned if no error.
     *                            Note that when returning errors for all attributes, the result is a two-dimensional array, like the following:
     *
     * ```php
     * array(
     *     'username' => array(
     *         'Username is required.',
     *         'Username must contain only word characters.',
     *     ),
     *     'email' => array(
     *         'Email address is invalid.',
     *     )
     * )
     * ```
     *
     * @see getFirstErrors
     * @see getFirstError
     */
    public function getErrors($attribute = null)
    {
        if ($attribute === null) {
            return $this->_errors;
        }

        return isset($this->_errors[$attribute]) ? $this->_errors[$attribute] : [];
    }

    /**
     * Returns the first error of every attribute in the model.
     *
     * @return array the first errors. An empty array will be returned if there is no error.
     * @see getErrors
     * @see getFirstError
     */
    public function getFirstErrors()
    {
        if (empty($this->_errors)) {
            return [];
        } else {
            $errors = [];
            foreach ($this->_errors as $attributeErrors) {
                if (current($attributeErrors)) {
                    $errors[] = current($attributeErrors);
                }
            }
        }

        return $errors;
    }

    /**
     * Returns the first error of the specified attribute.
     *
     * @param string $attribute attribute name.
     * @return string the error message. Null is returned if no error.
     * @see getErrors
     * @see getFirstErrors
     */
    public function getFirstError($attribute)
    {
        return isset($this->_errors[$attribute])
            ? reset($this->_errors[$attribute])
            : null;
    }

    /**
     * Adds a new error to the specified attribute.
     *
     * @param string $attribute attribute name
     * @param string $error     new error message
     */
    public function addError($attribute, $error = '')
    {
        $this->_errors[$attribute][] = $error;
    }

    public function addMultiErrors(array $errors)
    {
        if (empty($errors)) {
            return;
        }

        $this->_errors = array_merge($this->_errors, $errors);
    }

    /**
     * Removes errors for all attributes or a single attribute.
     *
     * @param string $attribute attribute name. Use null to remove errors for all attribute.
     */
    public function clearErrors($attribute = null)
    {
        if ($attribute === null) {
            $this->_errors = [];
        } else {
            unset($this->_errors[$attribute]);
        }
    }

    /**
     * Adds a new error to the specified attribute.
     *
     * @param string $error     new error message
     * @param string $placeholderName placeholder name
     */
    public function addErrorAsPlaceholder($error, $placeholderName = null)
    {
        if (empty($placeholderName)) {
            $placeholderName = 'e_' . $this->formName();
        }
        $this->_errors[$placeholderName][] = $error;
        $this->Rock->template->addPlaceholder($placeholderName, [$error], true);
    }

    /**
     * Generates a user friendly attribute label based on the give attribute name.
     * This is done by replacing underscores, dashes and dots with blanks and
     * changing the first letter of each word to upper case.
     * For example, 'department_name' or 'DepartmentName' will generate 'Department Name'.
     *
     * @param string $name the column name
     * @return string the attribute label
     */
    public function generateAttributeLabel($name)
    {
        return Inflector::camel2words($name, true);
    }

    /**
     * Returns attribute values.
     *
     * @param array $only  list of attributes whose value needs to be returned.
     *                      Defaults to null, meaning all attributes listed in @see attributes() will be returned.
     *                      If it is an array, only the attributes in the array will be returned.
     * @param array $exclude list of attributes whose value should NOT be returned.
     * @return array attribute values (name => value).
     */
    public function getAttributes(array $only = [], array $exclude = [])
    {
        $values = [];
        if (empty($only)) {
            $only = $this->attributes();
        }
        foreach ($only as $name) {
            $values[$name] = $this->$name;
        }
        foreach ($exclude as $name) {
            unset($values[$name]);
        }

        return $values;
    }


    /**
     * Sets the attribute values in a massive way.
     * @param array $values attribute values (name => value) to be assigned to the model.
     * @param boolean $safeOnly whether the assignments should only be done to the safe attributes.
     * A safe attribute is one that is associated with a validation rule in the current @see scenario .
     * @see safeAttributes()
     * @see attributes()
     */
    public function setAttributes(array $values, $safeOnly = true)
    {
        $attributes = array_flip($safeOnly ? $this->safeAttributes() : $this->attributes());
        foreach ($values as $name => $value) {
            if (isset($attributes[$name])) {
                $this->$name = $value;
            } elseif ($safeOnly) {
                $this->onUnsafeAttribute($name, $value);
            }
        }
    }

    /**
     * This method is invoked when an unsafe attribute is being massively assigned.
     * The default implementation will log a warning message if `DEBUG` is on.
     * It does nothing otherwise.
     * @param string $name the unsafe attribute name
     * @param mixed $value the attribute value
     */
    public function onUnsafeAttribute($name, $value)
    {
//        if (DEBUG) {
//            Rock::trace(__METHOD__, "Failed to set unsafe attribute '$name' in '" . get_class($this) . "'.");
//        }
    }
    /**
     * Returns the scenario that this model is used in.
     *
     * Scenario affects how validation is performed and which attributes can
     * be massively assigned.
     *
     * @return string the scenario that this model is in. Defaults to [[DEFAULT_SCENARIO]].
     */
    public function getScenario()
    {
        return $this->_scenario;
    }


    /**
     * Returns the attribute names that are safe to be massively assigned in the current scenario.
     * @return string[] safe attribute names
     */
    public function safeAttributes()
    {
        return $this->attributes();
    }



    /**
     * Populates the model with the data from end user.
     * The data to be loaded is `$data[formName]`, where `formName` refers to the value of @see formName() .
     * If @see formName() is empty, the whole `$data` array will be used to populate the model.
     * The data being populated is subject to the safety check by @see setAttributes() .
     * @param array $data the data array. This is usually `$_POST` or `$_GET`, but can also be any valid array
     * supplied by end user.
     * @return bool whether the model is successfully populated with some data.
     */
    public function load($data)
    {
        $scope = $this->formName();
        if ($scope == '') {
            $this->setAttributes($data);
            return true;
        } elseif (isset($data[$scope])) {
            $this->setAttributes($data[$scope]);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Populates a set of models with the data from end user.
     * This method is mainly used to collect tabular data input.
     * The data to be loaded for each model is `$data[formName][index]`, where `formName`
     * refers to the value of @see formName() , and `index` the index of the model in the `$models` array.
     * If @see formName() is empty, `$data[index]` will be used to populate each model.
     * The data being populated to each model is subject to the safety check by @see setAttributes() .
     * @param array $models the models to be populated. Note that all models should have the same class.
     * @param array $data the data array. This is usually `$_POST` or `$_GET`, but can also be any valid array
     * supplied by end user.
     * @return bool whether the model is successfully populated with some data.
     */
    public static function loadMultiple($models, $data)
    {
        /** @var Model $model */
        $model = reset($models);
        if ($model === false) {
            return false;
        }
        $success = false;
        $scope = $model->formName();
        foreach ($models as $i => $model) {
            if ($scope == '') {
                if (isset($data[$i])) {
                    $model->setAttributes($data[$i]);
                    $success = true;
                }
            } elseif (isset($data[$scope][$i])) {
                $model->setAttributes($data[$scope][$i]);
                $success = true;
            }
        }

        return $success;
    }

    /**
     * Returns the list of fields that should be returned by default by @see toArray() when no specific fields are specified.
     *
     * A field is a named element in the returned array by @see toArray() .
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
     * information. For example, depending on @see scenario or the privilege of the current application user,
     * you may return different sets of visible fields or filter out some fields.
     *
     * The default implementation of this method returns @see attributes() indexed by the same attribute names.
     *
     * @return array the list of field names or field definitions.
     * @see toArray()
     */
    public function fields()
    {
        $fields = $this->attributes();

        return array_combine($fields, $fields);
    }

    /**
     * Returns an iterator for traversing the attributes in the model.
     * This method is required by the interface IteratorAggregate.
     *
     * @param array $only
     * @param array $exclude
     * @return \ArrayIterator an iterator for traversing the items in the list.
     */
    public function getIterator(array $only = [], $exclude = [])
    {
        $attributes = $this->getAttributes($only, $exclude);
        return new \ArrayIterator($attributes);
    }

    /**
     * Returns whether there is an element at the specified offset.
     * This method is required by the SPL interface `ArrayAccess`.
     * It is implicitly called when you use something like `isset($model[$offset])`.
     *
     * @param mixed $offset the offset to check on
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->$offset !== null;
    }

    /**
     * Returns the element at the specified offset.
     * This method is required by the SPL interface `ArrayAccess`.
     * It is implicitly called when you use something like `$value = $model[$offset];`.
     *
     * @param mixed $offset the offset to retrieve element.
     * @return mixed the element at the offset, null if no element is found at the offset
     */
    public function offsetGet($offset)
    {
        return $this->$offset;
    }

    /**
     * Sets the element at the specified offset.
     * This method is required by the SPL interface `ArrayAccess`.
     * It is implicitly called when you use something like `$model[$offset] = $item;`.
     *
     * @param integer $offset the offset to set element
     * @param mixed   $item   the element value
     */
    public function offsetSet($offset, $item)
    {
        $this->$offset = $item;
    }

    /**
     * Sets the element value at the specified offset to null.
     * This method is required by the SPL interface `ArrayAccess`.
     * It is implicitly called when you use something like `unset($model[$offset])`.
     *
     * @param mixed $offset the offset to unset element
     */
    public function offsetUnset($offset)
    {
        $this->$offset = null;
    }

    protected function error($msg, $placeholder = null)
    {
        if (!isset($placeholder)) {
            $placeholder = 'e_' . $this->formName();
        }

        $this->Rock->template->addPlaceholder($placeholder, $msg, true);
    }
}