<?php

namespace rock\widgets;


use rock\components\Model;
use rock\db\ActiveRecordInterface;
use rock\template\Html;
use rock\template\HtmlException;

class ActiveHtml extends Html
{

    /**
     * Generates a label tag for the given model attribute.
     *
     * The label text is the label associated with the attribute, obtained via {@see \rock\components\Model::getAttributeLabel()}.
     *
     * @param Model  $model     the model object
     * @param string $attribute the attribute name or expression.
     *                          See {@see \rock\widgets\ActiveHtml::getAttributeName()} for the format
     *                          about attribute expression.
     * @param array  $options   the tag options in terms of name-value pairs. These will be rendered as
     *                          the attributes of the resulting tag. The values will be HTML-encoded using {@see \rock\template\Html::encode()} .
     *                          If a value is null, the corresponding attribute will not be rendered.
     *                          The following options are specially handled:
     *
     * - label: this specifies the label to be displayed. Note that this will NOT be {@see \rock\template\Html::encode()}.
     *   If this is not set, {@see \rock\components\Model::getAttributeLabel()} will be called to get the label for display
     *   (after encoding).
     *
     * See {@see \rock\template\Html::renderTagAttributes()} for details on how attributes are being rendered.
     *
     * @return string the generated label tag
     */
    public static function activeLabel($model, $attribute, $options = [])
    {
        $for = array_key_exists('for', $options) ? $options['for'] : static::getInputId($model, $attribute);
        $attribute = static::getAttributeName($attribute);
        $label = isset($options['label']) ? $options['label'] : static::encode($model->getAttributeLabel($attribute));
        unset($options['label'], $options['for']);

        return static::label($label, $for, $options);
    }

    /**
     * Generates an input tag for the given model attribute.
     * This method will generate the "name" and "value" tag attributes automatically for the model attribute
     * unless they are explicitly specified in `$options`.
     *
     * @param string $type      the input type (e.g. 'text', 'password')
     * @param Model  $model     the model object
     * @param string $attribute the attribute name or expression. See {@see \rock\widgets\ActiveHtml::getAttributeName()} for the format
     *                          about attribute expression.
     * @param array  $options   the tag options in terms of name-value pairs. These will be rendered as
     *                          the attributes of the resulting tag. The values will be HTML-encoded using {@see \rock\template\Html::encode()} .
     *                          See {@see \rock\template\Html::renderTagAttributes()} for details on how attributes are being rendered.
     * @return string the generated input tag
     */
    public static function activeInput($type, $model, $attribute, $options = [])
    {
        $name = isset($options['name']) ? $options['name'] : static::getInputName($model, $attribute);
        $value = isset($options['value']) ? $options['value'] : static::getAttributeValue($model, $attribute);
        if (!array_key_exists('id', $options)) {
            $options['id'] = static::getInputId($model, $attribute);
        }

        return static::input($type, $name, $value, $options);
    }

    /**
     * Generates a text input tag for the given model attribute.
     *
     * This method will generate the "name" and "value" tag attributes automatically for the model attribute
     * unless they are explicitly specified in `$options`.
     *
     * @param Model  $model     the model object
     * @param string $attribute the attribute name or expression. See {@see \rock\widgets\ActiveHtml::getAttributeName()} for the format
     *                          about attribute expression.
     * @param array  $options   the tag options in terms of name-value pairs. These will be rendered as
     *                          the attributes of the resulting tag. The values will be HTML-encoded using {@see \rock\template\Html::encode()} .
     *                          See {@see \rock\template\Html::renderTagAttributes()} for details on how attributes are being rendered.
     * @return string the generated input tag
     */
    public static function activeTextInput($model, $attribute, $options = [])
    {
        return static::activeInput('text', $model, $attribute, $options);
    }

    /**
     * Generates a hidden input tag for the given model attribute.
     *
     * This method will generate the "name" and "value" tag attributes automatically for the model attribute
     * unless they are explicitly specified in `$options`.
     *
     * @param Model  $model     the model object
     * @param string $attribute the attribute name or expression. See {@see \rock\widgets\ActiveHtml::getAttributeName()} for the format
     *                          about attribute expression.
     * @param array  $options   the tag options in terms of name-value pairs. These will be rendered as
     *                          the attributes of the resulting tag. The values will be HTML-encoded using {@see \rock\template\Html::encode()} .
     *                          See {@see \rock\template\Html::renderTagAttributes()} for details on how attributes are being rendered.
     * @return string the generated input tag
     */
    public static function activeHiddenInput($model, $attribute, $options = [])
    {
        return static::activeInput('hidden', $model, $attribute, $options);
    }

    /**
     * Generates a password input tag for the given model attribute.
     *
     * This method will generate the "name" and "value" tag attributes automatically for the model attribute
     * unless they are explicitly specified in `$options`.
     *
     * @param \rock\components\Model  $model     the model object
     * @param string $attribute the attribute name or expression. See {@see \rock\widgets\ActiveHtml::getAttributeName()} for the format
     *                          about attribute expression.
     * @param array  $options   the tag options in terms of name-value pairs. These will be rendered as
     *                          the attributes of the resulting tag. The values will be HTML-encoded using {@see \rock\template\Html::encode()}.
     *                          See {@see \rock\template\Html::renderTagAttributes()} for details on how attributes are being rendered.
     * @return string the generated input tag
     */
    public static function activePasswordInput($model, $attribute, $options = [])
    {
        return static::activeInput('password', $model, $attribute, $options);
    }

    /**
     * Generates a file input tag for the given model attribute.
     *
     * This method will generate the "name" and "value" tag attributes automatically for the model attribute
     * unless they are explicitly specified in `$options`.
     *
     * @param \rock\components\Model  $model     the model object
     * @param string $attribute the attribute name or expression. See {@see \rock\widgets\ActiveHtml::getAttributeName()} for the format
     *                          about attribute expression.
     * @param array  $options   the tag options in terms of name-value pairs. These will be rendered as
     *                          the attributes of the resulting tag. The values will be HTML-encoded using {@see \rock\template\Html::encode()} .
     *                          See {@see \rock\template\Html::renderTagAttributes()} for details on how attributes are being rendered.
     * @return string the generated input tag
     */
    public static function activeFileInput($model, $attribute, $options = [])
    {
        // add a hidden field so that if a model only has a file field, we can
        // still use isset($_POST[$modelClass]) to detect if the input is submitted
        return static::activeHiddenInput($model, $attribute, ['id' => null, 'value' => ''])
               . static::activeInput('file', $model, $attribute, $options);
    }

    /**
     * Generates a textarea tag for the given model attribute.
     *
     * The model attribute value will be used as the content in the textarea.
     *
     * @param \rock\components\Model  $model     the model object
     * @param string $attribute the attribute name or expression. See {@see \rock\widgets\ActiveHtml::getAttributeName()} for the format
     *                          about attribute expression.
     * @param array  $options   the tag options in terms of name-value pairs. These will be rendered as
     *                          the attributes of the resulting tag. The values will be HTML-encoded using {@see \rock\template\Html::encode()}.
     *                          See {@see \rock\template\Html::renderTagAttributes()} for details on how attributes are being rendered.
     * @return string the generated textarea tag
     */
    public static function activeTextarea($model, $attribute, $options = [])
    {
        $name = isset($options['name']) ? $options['name'] : static::getInputName($model, $attribute);
        $value = static::getAttributeValue($model, $attribute);
        if (!array_key_exists('id', $options)) {
            $options['id'] = static::getInputId($model, $attribute);
        }

        return static::textarea($name, $value, $options);
    }

    /**
     * Generates a radio button tag together with a label for the given model attribute.
     *
     * This method will generate the "checked" tag attribute according to the model attribute value.
     *
     * @param Model  $model     the model object
     * @param string $attribute the attribute name or expression. See {@see \rock\widgets\ActiveHtml::getAttributeName()} for the format
     *                          about attribute expression.
     * @param array  $options   the tag options in terms of name-value pairs. The following options are specially handled:
     *
     * - uncheck: string, the value associated with the uncheck state of the radio button. If not set,
     *   it will take the default value '0'. This method will render a hidden input so that if the radio button
     *   is not checked and is submitted, the value of this attribute will still be submitted to the server
     *   via the hidden input. If you do not want any hidden input, you should explicitly set this option as null.
     * - label: string, a label displayed next to the radio button.  It will NOT be HTML-encoded. Therefore you can pass
     *   in HTML code such as an image tag. If this is is coming from end users, you should {@see \rock\template\Html::encode()} it to prevent XSS attacks.
     *   The radio button will be enclosed by the label tag. Note that if you do not specify this option, a default label
     *   will be used based on the attribute label declaration in the model. If you do not want any label, you should
     *   explicitly set this option as null.
     * - labelOptions: array, the HTML attributes for the label tag. This is only used when the "label" option is specified.
     *
     * The rest of the options will be rendered as the attributes of the resulting tag. The values will
     * be HTML-encoded using {@see \rock\template\Html::encode()} . If a value is null, the corresponding attribute will not be rendered.
     *
     * See {@see \rock\template\Html::renderTagAttributes()} for details on how attributes are being rendered.
     *
     * @return string the generated radio button tag
     */
    public static function activeRadio($model, $attribute, $options = [])
    {
        $name = isset($options['name']) ? $options['name'] : static::getInputName($model, $attribute);
        $value = static::getAttributeValue($model, $attribute);
        if (!array_key_exists('value', $options)) {
            $options['value'] = '1';
        }
        if (!array_key_exists('uncheck', $options)) {
            $options['uncheck'] = '0';
        }
        if (!array_key_exists('label', $options)) {
            $options['label'] = static::encode($model->getAttributeLabel(static::getAttributeName($attribute)));
        }
        $checked = "$value" === "{$options['value']}";
        if (!array_key_exists('id', $options)) {
            $options['id'] = static::getInputId($model, $attribute);
        }

        return static::radio($name, $checked, $options);
    }

    /**
     * Generates a checkbox tag together with a label for the given model attribute.
     *
     * This method will generate the "checked" tag attribute according to the model attribute value.
     *
     * @param Model  $model     the model object
     * @param string $attribute the attribute name or expression. See {@see \rock\widgets\ActiveHtml::getAttributeName()} for the format
     *                          about attribute expression.
     * @param array  $options   the tag options in terms of name-value pairs. The following options are specially handled:
     *
     * - uncheck: string, the value associated with the uncheck state of the radio button. If not set,
     *   it will take the default value '0'. This method will render a hidden input so that if the radio button
     *   is not checked and is submitted, the value of this attribute will still be submitted to the server
     *   via the hidden input.
     * - label: string, a label displayed next to the checkbox.  It will NOT be HTML-encoded. Therefore you can pass
     *   in HTML code such as an image tag. If this is is coming from end users, you should {@see \rock\template\Html::encode()} it to prevent XSS attacks.
     *   The checkbox will be enclosed by the label tag. Note that if you do not specify this option, a default label
     *   will be used based on the attribute label declaration in the model. If you do not want any label, you should
     *   explicitly set this option as null.
     * - labelOptions: array, the HTML attributes for the label tag. This is only used when the "label" option is specified.
     *
     * The rest of the options will be rendered as the attributes of the resulting tag. The values will
     * be HTML-encoded using {@see \rock\template\Html::encode()} . If a value is null, the corresponding attribute will not be rendered.
     * See {@see \rock\template\Html::renderTagAttributes()} for details on how attributes are being rendered.
     *
     * @return string the generated checkbox tag
     */
    public static function activeCheckbox($model, $attribute, $options = [])
    {
        $name = isset($options['name']) ? $options['name'] : static::getInputName($model, $attribute);
        $value = static::getAttributeValue($model, $attribute);
        if (!array_key_exists('value', $options)) {
            $options['value'] = '1';
        }
        if (!array_key_exists('uncheck', $options)) {
            $options['uncheck'] = '0';
        }
        if (!array_key_exists('label', $options)) {
            $options['label'] = static::encode($model->getAttributeLabel(static::getAttributeName($attribute)));
        }
        $checked = "$value" === "{$options['value']}";
        if (!array_key_exists('id', $options)) {
            $options['id'] = static::getInputId($model, $attribute);
        }

        return static::checkbox($name, $checked, $options);
    }

    /**
     * Generates a drop-down list for the given model attribute.
     *
     * The selection of the drop-down list is taken from the value of the model attribute.
     *
     * @param Model  $model     the model object
     * @param string $attribute the attribute name or expression. See {@see \rock\widgets\ActiveHtml::getAttributeName()} for the format
     *                          about attribute expression.
     * @param array  $items     the option data items. The array keys are option values, and the array values
     *                          are the corresponding option labels. The array can also be nested (i.e. some array values are arrays too).
     *                          For each sub-array, an option group will be generated whose label is the key associated with the sub-array.
     *                          If you have a list of data models, you may convert them into the format described above using {@see \rock\helpers\ArrayHelper::map()} .
     *
     * Note, the values and labels will be automatically HTML-encoded by this method, and the blank spaces in
     * the labels will also be HTML-encoded.
     * @param array  $options   the tag options in terms of name-value pairs. The following options are specially handled:
     *
     * - prompt: string, a prompt text to be displayed as the first option;
     * - options: array, the attributes for the select option tags. The array keys must be valid option values,
     *   and the array values are the extra attributes for the corresponding option tags. For example,
     *
     *   ```php
     *   [
     *       'value1' => ['disabled' => true],
     *       'value2' => ['label' => 'value 2'],
     *   ];
     *   ```
     *
     * - groups: array, the attributes for the optgroup tags. The structure of this is similar to that of 'options',
     *   except that the array keys represent the optgroup labels specified in $items.
     * - encodeSpaces: bool, whether to encode spaces in option prompt and option value with `&nbsp;` character.
     *   Defaults to `false`.
     *
     * The rest of the options will be rendered as the attributes of the resulting tag. The values will
     * be HTML-encoded using {@see \rock\template\Html::encode()} . If a value is null, the corresponding attribute will not be rendered.
     * See {@see \rock\template\Html::renderTagAttributes()} for details on how attributes are being rendered.
     *
     * @return string the generated drop-down list tag
     */
    public static function activeDropDownList($model, $attribute, $items, $options = [])
    {
        if (empty($options['multiple'])) {
            return static::activeListInput('dropDownList', $model, $attribute, $items, $options);
        } else {
            return static::activeListBox($model, $attribute, $items, $options);
        }
    }

    /**
     * Generates a list box.
     *
     * The selection of the list box is taken from the value of the model attribute.
     *
     * @param \rock\components\Model  $model     the model object
     * @param string $attribute the attribute name or expression. See {@see \rock\widgets\ActiveHtml::getAttributeName()} for the format
     *                          about attribute expression.
     * @param array  $items     the option data items. The array keys are option values, and the array values
     *                          are the corresponding option labels. The array can also be nested (i.e. some array values are arrays too).
     *                          For each sub-array, an option group will be generated whose label is the key associated with the sub-array.
     *                          If you have a list of data models, you may convert them into the format described above using {@see \rock\helpers\ArrayHelper::map()}.
     *
     * Note, the values and labels will be automatically HTML-encoded by this method, and the blank spaces in
     * the labels will also be HTML-encoded.
     * @param array  $options   the tag options in terms of name-value pairs. The following options are specially handled:
     *
     * - prompt: string, a prompt text to be displayed as the first option;
     * - options: array, the attributes for the select option tags. The array keys must be valid option values,
     *   and the array values are the extra attributes for the corresponding option tags. For example,
     *
     *   ```php
     *   [
     *       'value1' => ['disabled' => true],
     *       'value2' => ['label' => 'value 2'],
     *   ];
     *   ```
     *
     * - groups: array, the attributes for the optgroup tags. The structure of this is similar to that of 'options',
     *   except that the array keys represent the optgroup labels specified in $items.
     * - unselect: string, the value that will be submitted when no option is selected.
     *   When this attribute is set, a hidden field will be generated so that if no option is selected in multiple
     *   mode, we can still obtain the posted unselect value.
     * - encodeSpaces: bool, whether to encode spaces in option prompt and option value with `&nbsp;` character.
     *   Defaults to `false`.
     *
     * The rest of the options will be rendered as the attributes of the resulting tag. The values will
     * be HTML-encoded using {@see \rock\template\Html::encode()} . If a value is null, the corresponding attribute will not be rendered.
     * See {@see \rock\template\Html::renderTagAttributes()} for details on how attributes are being rendered.
     *
     * @return string the generated list box tag
     */
    public static function activeListBox($model, $attribute, $items, $options = [])
    {
        return static::activeListInput('listBox', $model, $attribute, $items, $options);
    }

    /**
     * Generates a list of checkboxes.
     *
     * A checkbox list allows multiple selection, like {@see \rock\template\Html::listBox()}.
     * As a result, the corresponding submitted value is an array.
     * The selection of the checkbox list is taken from the value of the model attribute.
     *
     * @param Model  $model     the model object
     * @param string $attribute the attribute name or expression. See {@see \rock\widgets\ActiveHtml::getAttributeName()} for the format
     *                          about attribute expression.
     * @param array $items the data item used to generate the checkboxes.
     * The array keys are the checkbox values, and the array values are the corresponding labels.
     *                          Note that the labels will NOT be HTML-encoded, while the values will.
     * @param array  $options   options (name => config) for the checkbox list. The following options are specially handled:
     *
     * - unselect: string, the value that should be submitted when none of the checkboxes is selected.
     *   You may set this option to be null to prevent default value submission.
     *   If this option is not set, an empty string will be submitted.
     * - separator: string, the HTML code that separates items.
     * - item: callable, a callback that can be used to customize the generation of the HTML code
     *   corresponding to a single item in $items. The signature of this callback must be:
     *
     *   ```php
     *   function ($index, $label, $name, $checked, $value)
     *   ```
     *
     *   where $index is the zero-based index of the checkbox in the whole list; $label
     *   is the label for the checkbox; and $name, $value and $checked represent the name,
     *   value and the checked status of the checkbox input.
     *
     * See {@see \rock\template\Html::renderTagAttributes()} for details on how attributes are being rendered.
     *
     * @return string the generated checkbox list
     */
    public static function activeCheckboxList($model, $attribute, $items, $options = [])
    {
        return static::activeListInput('checkboxList', $model, $attribute, $items, $options);
    }

    /**
     * Generates a list of radio buttons.
     *
     * A radio button list is like a checkbox list, except that it only allows single selection.
     * The selection of the radio buttons is taken from the value of the model attribute.
     *
     * @param Model  $model     the model object
     * @param string $attribute the attribute name or expression. See {@see \rock\widgets\ActiveHtml::getAttributeName()} for the format
     *                          about attribute expression.
     * @param array $items the data item used to generate the radio buttons.
     * The array keys are the radio values, and the array values are the corresponding labels.
     *                          Note that the labels will NOT be HTML-encoded, while the values will.
     * @param array  $options   options (name => config) for the radio button list. The following options are specially handled:
     *
     * - unselect: string, the value that should be submitted when none of the radio buttons is selected.
     *   You may set this option to be null to prevent default value submission.
     *   If this option is not set, an empty string will be submitted.
     * - separator: string, the HTML code that separates items.
     * - item: callable, a callback that can be used to customize the generation of the HTML code
     *   corresponding to a single item in $items. The signature of this callback must be:
     *
     *   ```php
     *   function ($index, $label, $name, $checked, $value)
     *   ```
     *
     *   where $index is the zero-based index of the radio button in the whole list; $label
     *   is the label for the radio button; and $name, $value and $checked represent the name,
     *   value and the checked status of the radio button input.
     *
     * See {@see \rock\template\Html::renderTagAttributes()} for details on how attributes are being rendered.
     *
     * @return string the generated radio button list
     */
    public static function activeRadioList($model, $attribute, $items, $options = [])
    {
        return static::activeListInput('radioList', $model, $attribute, $items, $options);
    }

    /**
     * Generates a tag that contains the first validation error of the specified model attribute.
     *
     * Note that even if there is no validation error, this method will still return an empty error tag.
     *
     * @param Model  $model     the model object
     * @param string $attribute the attribute name or expression. See {@see \rock\widgets\ActiveHtml::getAttributeName()} for the format
     *                          about attribute expression.
     * @param array  $options   the tag options in terms of name-value pairs. The values will be HTML-encoded
     *                          using {@see \rock\template\Html::encode()} . If a value is null, the corresponding attribute will not be rendered.
     *
     * The following options are specially handled:
     *
     * - tag: this specifies the tag name. If not set, "div" will be used.
     * - encode: boolean, if set to false then value won't be encoded.
     *
     * See {@see \rock\template\Html::renderTagAttributes()} for details on how attributes are being rendered.
     *
     * @return string the generated label tag
     */
    public static function error($model, $attribute, $options = [])
    {
        $attribute = static::getAttributeName($attribute);
        $error = $model->getFirstError($attribute);
        if (empty($error)) {
            return '';
        }
        $tag = isset($options['tag']) ? $options['tag'] : 'div';
        $encode = !isset($options['encode']) || $options['encode'] !== false;
        unset($options['tag'], $options['encode']);

        return static::tag($tag, $encode ? static::encode($error) : $error, $options);
    }

    /**
     * Returns the value of the specified attribute name or expression.
     *
     * For an attribute expression like `[0]dates[0]`, this method will return the value of `$model->dates[0]`.
     * See {@see \rock\widgets\ActiveHtml::getAttributeName()} for more details about attribute expression.
     *
     * If an attribute value is an instance of {@see \rock\db\ActiveRecordInterface} or an array of such instances,
     * the primary value(s) of the AR instance(s) will be returned instead.
     *
     * @param \rock\components\Model  $model     the model object
     * @param string $attribute the attribute name or expression
     * @return string|array the corresponding attribute value
     * @throws HtmlException if the attribute name contains non-word characters.
     */
    public static function getAttributeValue($model, $attribute)
    {
        if (!preg_match('/(^|.*\])([\w\.]+)(\[.*|$)/', $attribute, $matches)) {
            throw new HtmlException('Attribute name must contain word characters only.');
        }
        $attribute = $matches[2];
        $value = $model->$attribute;
        if ($matches[3] !== '') {
            foreach (explode('][', trim($matches[3], '[]')) as $id) {
                if ((is_array($value) || $value instanceof \ArrayAccess) && isset($value[$id])) {
                    $value = $value[$id];
                } else {
                    return null;
                }
            }
        }
        // https://github.com/yiisoft/yii2/issues/1457
        if (is_array($value)) {
            foreach ($value as $i => $v) {
                if ($v instanceof ActiveRecordInterface) {
                    $v = $v->getPrimaryKey(false);
                    $value[$i] = is_array($v) ? json_encode($v) : $v;
                }
            }
        } elseif ($value instanceof ActiveRecordInterface) {
            $value = $value->getPrimaryKey(false);

            return is_array($value) ? json_encode($value) : $value;
        }

        return $value;
    }

    /**
     * Generates a list of input fields.
     *
     * This method is mainly called by {@see \rock\widgets\ActiveHtml::activeListBox()}, {@see \rock\widgets\ActiveHtml::activeRadioList()} and {@see \rock\widgets\ActiveHtml::activeCheckBoxList()}.
     *
     * @param string $type      the input type. This can be `listBox`, `radioList`, or `checkBoxList`.
     * @param Model  $model     the model object
     * @param string $attribute the attribute name or expression. See {@see \rock\widgets\ActiveHtml::getAttributeName()} for the format
     *                          about attribute expression.
     * @param array  $items     the data item used to generate the input fields.
     *                          The array keys are the labels, while the array values are the corresponding input values.
     *                          Note that the labels will NOT be HTML-encoded, while the values will.
     * @param array  $options   options (name => config) for the input list. The supported special options
     *                          depend on the input type specified by `$type`.
     * @return string the generated input list
     */
    protected static function activeListInput($type, $model, $attribute, $items, $options = [])
    {
        $name = isset($options['name']) ? $options['name'] : static::getInputName($model, $attribute);
        $selection = static::getAttributeValue($model, $attribute);
        if (!array_key_exists('unselect', $options)) {
            $options['unselect'] = '';
        }
        if (!array_key_exists('id', $options)) {
            $options['id'] = static::getInputId($model, $attribute);
        }

        return static::$type($name, $selection, $items, $options);
    }

    /**
     * Generates an appropriate input name for the specified attribute name or expression.
     *
     * This method generates a name that can be used as the input name to collect user input
     * for the specified attribute. The name is generated according to the @see Model::formName
     * of the model and the given attribute name. For example, if the form name of the `Post` model
     * is `Post`, then the input name generated for the `content` attribute would be `Post[content]`.
     *
     * See {@see \rock\widgets\ActiveHtml::getAttributeName()} for explanation of attribute expression.
     *
     * @param Model  $model     the model object
     * @param string $attribute the attribute name or expression
     * @return string the generated input name
     * @throws \rock\template\HtmlException if the attribute name contains non-word characters.
     */
    public static function getInputName($model, $attribute)
    {
        $formName = $model->formName();
        if (!preg_match('/(^|.*\])([\w\.]+)(\[.*|$)/', $attribute, $matches)) {
            throw new HtmlException('Attribute name must contain word characters only.');
        }
        $prefix = $matches[1];
        $attribute = $matches[2];
        $suffix = $matches[3];
        if ($formName === '' && $prefix === '') {
            return $attribute . $suffix;
        } elseif ($formName !== '') {
            return $formName . $prefix . "[$attribute]" . $suffix;
        } else {
            throw new HtmlException(get_class($model) . '::formName() cannot be empty for tabular inputs.');
        }
    }

    /**
     * Generates an appropriate input ID for the specified attribute name or expression.
     *
     * This method converts the result {@see \rock\widgets\ActiveHtml::getInputName()} into a valid input ID.
     * For example, if {@see \rock\widgets\ActiveHtml::getInputName()} returns `Post[content]`, this method will return `post-content`.
     *
     * @param \rock\components\Model  $model     the model object
     * @param string $attribute the attribute name or expression.
     *                          See {@see \rock\widgets\ActiveHtml::getAttributeName()} for explanation of attribute expression.
     * @return string the generated input ID
     * @throws \rock\template\HtmlException if the attribute name contains non-word characters.
     */
    public static function getInputId($model, $attribute)
    {
        $name = strtolower(static::getInputName($model, $attribute));

        return str_replace(['[]', '][', '[', ']', ' '], ['', '-', '-', '', '-'], $name);
    }

    /**
     * Returns the real attribute name from the given attribute expression.
     *
     * An attribute expression is an attribute name prefixed and/or suffixed with array indexes.
     * It is mainly used in tabular data input and/or input of array type. Below are some examples:
     *
     * - `[0]content` is used in tabular data input to represent the "content" attribute
     *   for the first model in tabular input;
     * - `dates[0]` represents the first array element of the "dates" attribute;
     * - `[0]dates[0]` represents the first array element of the "dates" attribute
     *   for the first model in tabular input.
     *
     * If `$attribute` has neither prefix nor suffix, it will be returned back without change.
     *
     * @param string $attribute the attribute name or expression
     * @return string the attribute name without prefix and suffix.
     * @throws \rock\template\HtmlException if the attribute name contains non-word characters.
     */
    public static function getAttributeName($attribute)
    {
        if (preg_match('/(^|.*\])([\w\.]+)(\[.*|$)/', $attribute, $matches)) {
            return $matches[2];
        } else {
            throw new HtmlException('Attribute name must contain word characters only.');
        }
    }
}