<?php

namespace rock\widgets;

use rock\base\BaseException;
use rock\base\ComponentsInterface;
use rock\base\ComponentsTrait;
use rock\base\Model;
use rock\base\Widget;
use rock\cache\CacheInterface;
use rock\di\Container;
use rock\filters\RateLimiter;
use rock\helpers\Html;
use rock\helpers\Json;
use rock\log\Log;
use rock\Rock;

class ActiveField implements ComponentsInterface
{
    use ComponentsTrait;

    /**
     * @var ActiveForm the form that this field is associated with.
     */
    public $form;
    /**
     * @var Model the data model that this field is associated with
     */
    public $model;
    /**
     * @var string the model attribute that this field is associated with
     */
    public $attribute;
    /**
     * @var array the HTML attributes (name-value pairs) for the field container tag.
     * The values will be HTML-encoded using {@see \rock\helpers\Html::encode()}.
     * If a value is null, the corresponding attribute will not be rendered.
     * The following special options are recognized:
     *
     * - tag: the tag name of the container element. Defaults to "div".
     *
     * @see \rock\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    public $options = ['class' => 'form-group'];
    public $required = false;
    public $rateLimiter = [];

    /**
     * @var string the template that is used to arrange the label, the input field, the error message and the hint text.
     * The following tokens will be replaced when `render()` is called: `{label}`, `{input}`, `{error}` and `{hint}`.
     */
    public $template = "{label}\n{input}\n{hint}\n{error}";
    /**
     * @var array the default options for the input tags. The parameter passed to individual input methods
     * (e.g. `textInput()`) will be merged with this property when rendering the input tag.
     * @see \rock\helpers\Html::Html::renderTagAttributes()} for details on how attributes are being rendered.
     */
    public $inputOptions = ['class' => 'form-control'];
    /**
     * @var array the default options for the error tags. The parameter passed to `error()` will be
     * merged with this property when rendering the error tag.
     * The following special options are recognized:
     *
     * - tag: the tag name of the container element. Defaults to "div".
     *
     * @see \rock\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    public $errorOptions = ['class' => 'form-error'];
    /**
     * @var array the default options for the error tags. The parameter passed to `error()` will be
     * merged with this property when rendering the error tag.
     * The following special options are recognized:
     *
     * - tag: the tag name of the container element. Defaults to "div".
     *
     * @see \rock\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    public $ngErrorOptions = ['class' => 'form-error hide'];
    public $ngErrorMessages = [];
    /**
     * @var array the default options for the label tags. The parameter passed to `label()` will be
     * merged with this property when rendering the label tag.
     * @see \rock\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    public $labelOptions = ['class' => 'form-label'];
    /**
     * @var array the default options for the hint tags. The parameter
     * passed to @see hint() will be
     * merged with this property when rendering the hint tag.
     * The following special options are recognized:
     *
     * - tag: the tag name of the container element. Defaults to `div`.
     *
     * @see \rock\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    public $hintOptions = ['class' => 'form-hint'];
    /**
     * @var boolean whether to enable client-side data validation.
     * If not set, it will take the value of {@see \rock\widgets\ActiveForm::enableClientValidation}.
     */
    public $enableClientValidation = true;
    /**
     * @var boolean whether to perform validation when the input field loses focus and its value is found changed.
     * If not set, it will take the value of {@see \rock\widgets\ActiveForm::validateOnChanged}.
     */
    public $validateOnChanged = false;
    /**
     * @var array different parts of the field (e.g. input, label). This will be used together with
     * {@see \rock\widgets\ActiveField::$template} to generate the final field HTML code. The keys are the
     * token names in {@see \rock\widgets\ActiveField::$template} ,
     * while the values are the corresponding HTML code. Valid tokens include `{input}`, `{label}` and `{error}`.
     * Note that you normally don't need to access this property directly as
     * it is maintained by various methods of this class.
     */
    public $parts = [];
    /**
     * @var string|array|CacheInterface the cache object or the ID of the cache application component
     * that is used for query caching.
     * @see enableCache
     */
    public $cache = 'cache';
    /**
     * @var boolean whether to enable query caching.
     * Note that in order to enable query caching, a valid cache component as specified
     * by `cacheClass` must be enabled and `enableCache` must be set true.
     *
     * Methods @see beginCache
     * and @see endCache can be used as shortcuts to turn on
     * and off query caching on the fly.
     * @see cacheExpire
     * @see cacheClass
     * @see cacheTags
     * @see beginCache()
     * @see endCache()
     */
    public $enableCache = false;
    /**
     * @var integer number of seconds that query results can remain valid in cache.
     * Defaults to 0, meaning 0 seconds, or one hour.
     * Use 0 to indicate that the cached data will never expire.
     * @see enableCache
     */
    public $cacheExpire = 0;
    /**
     * @var string[] the dependency that will be used when saving query results into cache.
     * Defaults to null, meaning no dependency.
     * @see enableCache
     */
    public $cacheTags;
    /** @var string */
    protected $formName = 'form';

    public function init()
    {
        if ($formName = $this->model->formName()) {
            $this->formName = $formName;
        }

        if (!is_object($this->cache)) {
            $this->cache = Container::load($this->cache);
        }
    }

    /**
     * PHP magic method that returns the string representation of this object.
     *
     * @return string the string representation of this object.
     */
    public function __toString()
    {
        // __toString cannot throw exception
        // use trigger_error to bypass this limitation
        try {
            return $this->render();
        } catch (\Exception $e) {
            if (class_exists('\rock\log\Log')) {

                Log::err(BaseException::convertExceptionToString($e));
            }
            return '';
        }
    }

    /**
     * Renders the whole field.
     * This method will generate the label, error tag, input tag and hint tag (if any), and
     * assemble them into HTML according to {@see \rock\widgets\ActiveField::$template} .
     *
     * @param string|callable $content the content within the field container.
     *                                 If null (not set), the default methods will be called to generate the label, error tag and input tag,
     *                                 and use them as the content.
     *                                 If a callable, it will be called to generate the content. The signature of the callable should be:
     *
     * ```php
     * function ($field) {
     *     return $html;
     * }
     * ```
     *
     * @return string the rendering result
     */
    public function render($content = null)
    {
        if ($this->checkRateLimiter()) {
            return '';
        }
        if ($content === null) {
            if (!isset($this->parts['{input}'])) {
                $this->inputOptions = $this->calculateClientInputOption($this->inputOptions);
                $this->parts['{input}'] = Html::activeTextInput($this->model, $this->attribute, $this->inputOptions);
            }
            if (!isset($this->parts['{label}'])) {
                $this->parts['{label}'] = Html::activeLabel($this->model, $this->attribute, $this->labelOptions);
            }
            if (!isset($this->parts['{error}'])) {
                $this->parts['{error}'] = $this->renderErrors();
            }
            if (!isset($this->parts['{hint}'])) {
                $this->parts['{hint}'] = '';
            }
            $content = strtr($this->template, $this->parts);
        } elseif (!is_string($content)) {
            $content = call_user_func($content, $this);
        }

        return $this->begin() . "\n" . $content . "\n" . $this->end();
    }

    protected function checkRateLimiter()
    {
        if (!empty($this->rateLimiter)) {
            if (!isset($this->rateLimiter[2])) {
                $this->rateLimiter[2] = true;
            }
            list($limit, $period, $checked) = $this->rateLimiter;
            $rateLimiter = new RateLimiter(
                [
                    'enableRateLimitHeaders' => false,
                    'dependency' => isset($this->form->submitted) ? $this->form->submitted : true
                ]);
            if ($rateLimiter->check($limit, $period, get_class($this->model) . '::' . $this->attribute) === $checked) {
                return true;
            }
        }

        return false;
    }

    public function calculateClientInputOption($options = [])
    {
        $formName = $this->formName;
        if (!isset($options['data-ng-model'])) {
            $options['data-ng-model'] = isset($formName)
                ? "{$formName}.values.{$this->attribute}"
                : "form.values.{$this->attribute}";
        }
        if ($this->enableClientValidation && !isset($options['data-ng-class'])) {
            $options['data-ng-class'] = isset($formName)
                ? 'showHighlightError("' . $formName . '[' . $this->attribute . ']")'
                : 'showHighlightError("' . $this->attribute . '")';
        }
        if ($this->enableClientValidation && $this->validateOnChanged) {
            $options['data-rock-form-focus'] = '';
        }
        if (isset($options['value']) && empty($options['value']) && !isset($options['data-rock-reset-field'])) {
            $options['data-rock-reset-field'] = '';
        }
        return $this->calculateValidateOptions($options);
    }

    protected function calculateValidateOptions(array $options)
    {
        if (!$this->enableClientValidation) {
            return $options;
        }
        foreach ($this->model->rules() as $rule) {
            list($type, $attributes) = $rule;
            if ($type === Model::RULE_VALIDATE && in_array($this->attribute, (array)$attributes, true)) {
                $rule = array_slice($rule, 2);
                foreach ($rule as $ruleName => $args) {
                    if (is_int($ruleName)) {
                        $ruleName = $args;
                        $args = [];
                    }
                    if ($ruleName === 'length' && !isset($options['data-ng-minlength']) && !isset($options['data-ng-maxlength'])) {
                        $options['data-ng-minlength'] = $args[0];
                        $options['data-ng-maxlength'] = $args[1];
                        continue;
                    }
                    if ($ruleName === 'max' && !isset($options['data-ng-maxlength'])) {
                        $options['data-ng-maxlength'] = $args[0];
                        continue;
                    }
                    if ($ruleName === 'min' && !isset($options['data-ng-minlength'])) {
                        $options['data-ng-minlength'] = $args[0];
                        continue;
                    }
                    if ($ruleName === 'email' && !isset($options['data-ng-pattern'])) {
                        $options['data-ng-pattern'] = '/^([\\wА-яё]+[\\wА-яё\\.\\+\\-]+)?[\\wА-яё]+@([\\wА-яё]+\\.)+[\\wА-яё]+$/i';
                        continue;
                    }
                    if ($ruleName === 'regex' && !isset($options['data-ng-pattern'])) {
                        $options['data-ng-pattern'] = $args[0];
                        continue;
                    }
                    if ($ruleName === 'required' && !isset($options['data-ng-required'])) {
                        $options['data-ng-required'] = 'true';
                    }
                }
            }
        }
        return $options;
    }

    protected function renderErrors()
    {
        $result = '';
        if ($this->ngErrorMessages) {
            if (is_array($this->ngErrorMessages)) {
                $this->ngErrorMessages = Json::encode($this->ngErrorMessages);
            }
            $this->ngErrorOptions['data-ng-repeat'] = '(error, errorMsg) in ' . $this->ngErrorMessages;
            $this->ngErrorOptions['data-ng-class'] = isset($this->formName)
                ? 'showError("' . $this->formName . '[' . $this->attribute . ']", error)'
                : 'showError("' . $this->attribute . '", error)';
            $this->ngErrorOptions['data-ng-bind'] = 'errorMsg';
            $tag = isset($this->ngErrorOptions['tag']) ? $this->ngErrorOptions['tag'] : 'div';
            unset($this->ngErrorOptions['tag']);
            $result .= Html::tag($tag, '', $this->ngErrorOptions) . "\n";
        }
        $this->errorOptions['data-ng-class'] = isset($this->formName)
            ? 'hideError("' . $this->formName . '[' . $this->attribute . ']", error)'
            : 'hideError("' . $this->attribute . '", error)';
        $result .= Html::error($this->model, $this->attribute, $this->errorOptions);

        return $result;
    }

    /**
     * Renders the opening tag of the field container.
     *
     * @return string the rendering result.
     */
    public function begin()
    {
        $inputID = Html::getInputId($this->model, $this->attribute);
        $attribute = Html::getAttributeName($this->attribute);
        $options = $this->options;
        $class = isset($options['class']) ? [$options['class']] : [];
        $class[] = "field-$inputID";
        if (isset($this->form) && $this->model->isAttributeRequired($attribute)) {
            $class[] = $this->form->requiredCssClass;
        }
        if ($this->model->hasErrors($attribute) && isset($this->form)) {
            $class[] = $this->form->errorCssClass;
        }
        $options['class'] = implode(' ', $class);
        $tag = Html::remove($options, 'tag', 'div');

        return Html::beginTag($tag, $options);
    }

    /**
     * Renders the closing tag of the field container.
     *
     * @return string the rendering result.
     */
    public function end()
    {
        return Html::endTag(isset($this->options['tag']) ? $this->options['tag'] : 'div');
    }

    /**
     * Generates a label tag for {@see \rock\widgets\ActiveField::$attribute}.
     *
     * @param string|boolean $label   the label to use. If null, the label will be generated
     *                                via {@see \rock\base\Model::getAttributeLabel()}.
     *                                If false, the generated field will not contain the label part. Note that this will NOT be {@see \rock\helpers\Html::encode()}.
     * @param array          $options the tag options in terms of name-value pairs. It will be merged
     *                                with {@see \rock\widgets\ActiveField::$labelOptions}.
     *                                The options will be rendered as the attributes of the resulting tag. The values will be HTML-encoded
     *                                using {@see \rock\helpers\Html::encode()}. If a value is null, the corresponding attribute will not be rendered.
     * @return static the field object itself
     */
    public function label($label = null, $options = [])
    {
        if ($label === false) {
            $this->parts['{label}'] = '';

            return $this;
        }
        $options = array_merge($this->labelOptions, $options);
        if ($label !== null) {
            $options['label'] = $label;
        }
        $this->parts['{label}'] = Html::activeLabel($this->model, $this->attribute, $options);

        return $this;
    }

    /**
     * Generates a tag that contains the first validation error of {@see \rock\widgets\ActiveField::$attribute}.
     *
     * Note that even if there is no validation error, this method will still return an empty error tag.
     *
     * @param array|boolean $options the tag options in terms of name-value pairs. It will be merged
     *                               with @see errorOptions .
     *                               The options will be rendered as the attributes of the resulting tag. The values will be HTML-encoded
     *                               using {@see \rock\helpers\Html::encode()} . If a value is null, the corresponding attribute will not be rendered.
     *
     * The following options are specially handled:
     *
     * - tag: this specifies the tag name. If not set, `div` will be used.
     *
     * If this parameter is false, no error tag will be rendered.
     *
     * @return static the field object itself
     */
    public function error($options = [])
    {
        if ($options === false) {
            $this->parts['{error}'] = '';

            return $this;
        }
        $options = array_merge($this->errorOptions, $options);
        $this->parts['{error}'] = Html::error($this->model, $this->attribute, $options);

        return $this;
    }

    /**
     * Renders the hint tag.
     *
     * @param string $content the hint content. It will NOT be HTML-encoded.
     * @param array  $options the tag options in terms of name-value pairs. These will be rendered as
     *                        the attributes of the hint tag. The values will be HTML-encoded using {@see \rock\helpers\Html::encode()}.
     *
     * The following options are specially handled:
     *
     * - tag: this specifies the tag name. If not set, `div` will be used.
     *
     * @return static the field object itself
     */
    public function hint($content, $options = [])
    {
        $options = array_merge($this->hintOptions, $options);
        $tag = Html::remove($options, 'tag', 'div');
        $this->parts['{hint}'] = Html::tag($tag, $content, $options);

        return $this;
    }

    /**
     * Renders an input tag.
     *
     * @param string $type    the input type (e.g. 'text', 'password')
     * @param array  $options the tag options in terms of name-value pairs. These will be rendered as
     *                        the attributes of the resulting tag. The values will be HTML-encoded using {@see \rock\helpers\Html::encode()}.
     * @return static the field object itself
     */
    public function input($type, $options = [])
    {
        $options = array_merge($this->inputOptions, $options);
        $options = $this->calculateClientInputOption($options);
        $this->adjustLabelFor($options);
        $this->parts['{input}'] = Html::activeInput($type, $this->model, $this->attribute, $options);

        return $this;
    }

    /**
     * Adjusts the "for" attribute for the label based on the input options.
     *
     * @param array $options the input options
     */
    protected function adjustLabelFor($options)
    {
        if (isset($options['id']) && !isset($this->labelOptions['for'])) {
            $this->labelOptions['for'] = $options['id'];
        }
    }

    /**
     * Renders a text input.
     *
     * This method will generate the "name" and "value" tag attributes automatically for the model attribute
     * unless they are explicitly specified in `$options`.
     *
     * @param array $options the tag options in terms of name-value pairs. These will be rendered as
     *                       the attributes of the resulting tag. The values will be HTML-encoded using {@see \rock\helpers\Html::encode()}.
     * @return static the field object itself
     */
    public function textInput($options = [])
    {
        $options = array_merge($this->inputOptions, $options);
        $options = $this->calculateClientInputOption($options);
        $this->adjustLabelFor($options);
        $this->parts['{input}'] = Html::activeTextInput($this->model, $this->attribute, $options);

        return $this;
    }

    /**
     * Renders a hidden input.
     *
     * Note that this method is provided for completeness. In most cases because you do not need
     * to validate a hidden input, you should not need to use this method. Instead, you should
     * use @see Html::activeHiddenInput() .
     *
     * This method will generate the "name" and "value" tag attributes automatically for the model attribute
     * unless they are explicitly specified in `$options`.
     *
     * @param array $options the tag options in terms of name-value pairs. These will be rendered as
     *                       the attributes of the resulting tag. The values will be HTML-encoded using {@see \rock\helpers\Html::encode()} .
     * @return static the field object itself
     */
    public function hiddenInput($options = [])
    {
        $options = array_merge($this->inputOptions, $options);
        $options = $this->calculateClientInputOption($options);
        if (!isset($options['data-ng-init']) && isset($options['value']) && trim($options['value']) !== '') {
            $options['data-ng-init'] = isset($this->formName)
                ? "{$this->formName}.values.{$this->attribute}={$options['value']}"
                : "form.values.{$this->attribute}={$options['value']}";
        }
        $this->adjustLabelFor($options);
        $this->parts['{input}'] = Html::activeHiddenInput($this->model, $this->attribute, $options);

        return $this;
    }

    /**
     * Renders a password input.
     *
     * This method will generate the "name" and "value" tag attributes automatically for the model attribute
     * unless they are explicitly specified in `$options`.
     *
     * @param array $options the tag options in terms of name-value pairs. These will be rendered as
     *                       the attributes of the resulting tag. The values will be HTML-encoded using {@see \rock\helpers\Html::encode()} .
     * @return static the field object itself
     */
    public function passwordInput($options = [])
    {
        $options = array_merge($this->inputOptions, $options);
        $options = $this->calculateClientInputOption($options);
        $this->adjustLabelFor($options);
        $this->parts['{input}'] = Html::activePasswordInput($this->model, $this->attribute, $options);

        return $this;
    }

    /**
     * Renders a file input.
     *
     * This method will generate the "name" and "value" tag attributes automatically for the model attribute
     * unless they are explicitly specified in `$options`.
     *
     * @param array $options the tag options in terms of name-value pairs. These will be rendered as
     *                       the attributes of the resulting tag. The values will be HTML-encoded using {@see \rock\helpers\Html::encode()} .
     * @return static the field object itself
     */
    public function fileInput($options = [])
    {
        if ($this->inputOptions !== ['class' => 'form-control']) {
            $options = array_merge($this->inputOptions, $options);
        }
        $options = $this->calculateClientInputOption($options);
        $this->adjustLabelFor($options);
        $this->parts['{input}'] = Html::activeFileInput($this->model, $this->attribute, $options);

        return $this;
    }

    /**
     * Renders a text area.
     *
     * The model attribute value will be used as the content in the textarea.
     *
     * @param array $options the tag options in terms of name-value pairs. These will be rendered as
     *                       the attributes of the resulting tag. The values will be HTML-encoded using {@see \rock\helpers\Html::encode()} .
     * @return static the field object itself
     */
    public function textarea($options = [])
    {
        $options = array_merge($this->inputOptions, $options);
        $options = $this->calculateClientInputOption($options);
        $this->adjustLabelFor($options);
        $this->parts['{input}'] = Html::activeTextarea($this->model, $this->attribute, $options);

        return $this;
    }

    /**
     * Renders a radio button.
     *
     * This method will generate the "checked" tag attribute according to the model attribute value.
     *
     * @param array   $options         the tag options in terms of name-value pairs. The following options are specially handled:
     *
     * - uncheck: string, the value associated with the uncheck state of the radio button. If not set,
     *   it will take the default value '0'. This method will render a hidden input so that if the radio button
     *   is not checked and is submitted, the value of this attribute will still be submitted to the server
     *   via the hidden input.
     * - label: string, a label displayed next to the radio button.  It will NOT be HTML-encoded. Therefore you can pass
     *   in HTML code such as an image tag. If this is is coming from end users, you should {@see \rock\helpers\Html::encode()} it to prevent XSS attacks.
     *   When this option is specified, the radio button will be enclosed by a label tag.
     * - labelOptions: array, the HTML attributes for the label tag. This is only used when the "label" option is specified.
     *
     * The rest of the options will be rendered as the attributes of the resulting tag. The values will
     * be HTML-encoded using {@see \rock\helpers\Html::encode()} . If a value is null, the corresponding attribute will not be rendered.
     * @param boolean $enclosedByLabel whether to enclose the radio within the label.
     *                                 If true, the method will still use `template` to layout the checkbox and the error message
     *                                 except that the radio is enclosed by the label tag.
     * @return static the field object itself
     */
    public function radio($options = [], $enclosedByLabel = true)
    {
        $options = $this->calculateClientInputOption($options);
        if ($enclosedByLabel) {
            $this->parts['{input}'] = Html::activeRadio($this->model, $this->attribute, $options);
            $this->parts['{label}'] = '';
        } else {
            if (isset($options['label']) && !isset($this->parts['{label}'])) {
                $this->parts['{label}'] = $options['label'];
                if (!empty($options['labelOptions'])) {
                    $this->labelOptions = $options['labelOptions'];
                }
            }
            unset($options['label'], $options['labelOptions']);
            $this->parts['{input}'] = Html::activeRadio($this->model, $this->attribute, $options);
        }
        $this->adjustLabelFor($options);

        return $this;
    }

    /**
     * Renders a checkbox.
     *
     * This method will generate the "checked" tag attribute according to the model attribute value.
     *
     * @param array   $options         the tag options in terms of name-value pairs. The following options are specially handled:
     *
     * - uncheck: string, the value associated with the uncheck state of the radio button. If not set,
     *   it will take the default value '0'. This method will render a hidden input so that if the radio button
     *   is not checked and is submitted, the value of this attribute will still be submitted to the server
     *   via the hidden input.
     * - label: string, a label displayed next to the checkbox.  It will NOT be HTML-encoded. Therefore you can pass
     *   in HTML code such as an image tag. If this is is coming from end users, you should {@see \rock\helpers\Html::encode()} it to prevent XSS attacks.
     *   When this option is specified, the checkbox will be enclosed by a label tag.
     * - labelOptions: array, the HTML attributes for the label tag. This is only used when the "label" option is specified.
     *
     * The rest of the options will be rendered as the attributes of the resulting tag. The values will
     * be HTML-encoded using {@see \rock\helpers\Html::encode()} . If a value is null, the corresponding attribute will not be rendered.
     * @param boolean $enclosedByLabel whether to enclose the checkbox within the label.
     *                                 If true, the method will still use {@see \rock\widgets\ActiveField::$template} to layout the checkbox and the error message
     *                                 except that the checkbox is enclosed by the label tag.
     * @return static the field object itself
     */
    public function checkbox($options = [], $enclosedByLabel = true)
    {
        $options = $this->calculateClientInputOption($options);
        if ($enclosedByLabel) {
            $this->parts['{input}'] = Html::activeCheckbox($this->model, $this->attribute, $options);
            $this->parts['{label}'] = '';
        } else {
            if (isset($options['label']) && !isset($this->parts['{label}'])) {
                $this->parts['{label}'] = $options['label'];
                if (!empty($options['labelOptions'])) {
                    $this->labelOptions = $options['labelOptions'];
                }
            }
            unset($options['labelOptions']);
            $options['label'] = null;
            $this->parts['{input}'] = Html::activeCheckbox($this->model, $this->attribute, $options);
        }
        $this->adjustLabelFor($options);

        return $this;
    }

    /**
     * Renders a drop-down list.
     * The selection of the drop-down list is taken from the value of the model attribute.
     *
     * @param array|callable $items   the option data items. The array keys are option values, and the array values
     *                                are the corresponding option labels. The array can also be nested (i.e. some array values are arrays too).
     *                                For each sub-array, an option group will be generated whose label is the key associated with the sub-array.
     *                                If you have a list of data models, you may convert them into the format described above using {@see \rock\helpers\ArrayHelper::map()}.
     *
     * Note, the values and labels will be automatically HTML-encoded by this method, and the blank spaces in
     * the labels will also be HTML-encoded.
     * @param array          $options the tag options in terms of name-value pairs. The following options are specially handled:
     *
     * - prompt: string, a prompt text to be displayed as the first option;
     * - options: array, the attributes for the select option tags. The array keys must be valid option values,
     *   and the array values are the extra attributes for the corresponding option tags. For example,
     *
     * ```php
     * [
     *     'value1' => ['disabled' => true],
     *     'value2' => ['label' => 'value 2'],
     * ];
     * ```
     *
     * - groups: array, the attributes for the optgroup tags. The structure of this is similar to that of 'options',
     *   except that the array keys represent the optgroup labels specified in `$items`.
     *
     * The rest of the options will be rendered as the attributes of the resulting tag. The values will
     * be HTML-encoded using {@see \rock\helpers\Html::encode()} . If a value is null, the corresponding attribute will not be rendered.
     *
     * @return static the field object itself
     */
    public function dropDownList($items, $options = [])
    {
        $cacheKey = $this->getCacheKey(__METHOD__);
        if (($this->parts['{input}'] = $this->cache->get($cacheKey)) !== false) {
            return $this;
        }
        if ($items instanceof \Closure) {
            $items = call_user_func($items, $this);
        }
        $options = array_merge($this->inputOptions, $options);
        $options = $this->calculateClientInputOption($options);
        $this->adjustLabelFor($options);
        $this->parts['{input}'] = Html::activeDropDownList($this->model, $this->attribute, $items, $options);
        $this->cache->set($cacheKey, $this->parts['{input}']);

        return $this;
    }

    /**
     * Renders a list box.
     * 
     * The selection of the list box is taken from the value of the model attribute.
     *
     * @param array $items   the option data items. The array keys are option values, and the array values
     *                       are the corresponding option labels. The array can also be nested (i.e. some array values are arrays too).
     *                       For each sub-array, an option group will be generated whose label is the key associated with the sub-array.
     *                       If you have a list of data models, you may convert them into the format described above using {@see \rock\helpers\ArrayHelper::map()} .
     *
     * Note, the values and labels will be automatically HTML-encoded by this method, and the blank spaces in
     * the labels will also be HTML-encoded.
     * @param array $options the tag options in terms of name-value pairs. The following options are specially handled:
     *
     * - prompt: string, a prompt text to be displayed as the first option;
     * - options: array, the attributes for the select option tags. The array keys must be valid option values,
     *   and the array values are the extra attributes for the corresponding option tags. For example,
     *
     * ```php
     * [
     *     'value1' => ['disabled' => true],
     *     'value2' => ['label' => 'value 2'],
     * ];
     * ```
     *
     * - groups: array, the attributes for the optgroup tags. The structure of this is similar to that of 'options',
     *   except that the array keys represent the optgroup labels specified in `$items`.
     * - unselect: string, the value that will be submitted when no option is selected.
     *   When this attribute is set, a hidden field will be generated so that if no option is selected in multiple
     *   mode, we can still obtain the posted unselect value.
     *
     * The rest of the options will be rendered as the attributes of the resulting tag. The values will
     * be HTML-encoded using {@see \rock\helpers\Html::encode()} . If a value is null, the corresponding attribute will not be rendered.
     *
     * @return static the field object itself
     */
    public function listBox($items, $options = [])
    {
        $cacheKey = $this->getCacheKey(__METHOD__);
        if (($this->parts['{input}'] = $this->cache->get($cacheKey)) !== false) {
            return $this;
        }
        if ($items instanceof \Closure) {
            $items = call_user_func($items, $this);
        }
        $options = array_merge($this->inputOptions, $options);
        $this->adjustLabelFor($options);
        $this->parts['{input}'] = Html::activeListBox($this->model, $this->attribute, $items, $options);
        $this->cache->set($cacheKey, $this->parts['{input}']);

        return $this;
    }

    /**
     * Renders a list of checkboxes.
     * 
     * A checkbox list allows multiple selection, like {@see \rock\widgets\ActiveField::listBox()} .
     * As a result, the corresponding submitted value is an array.
     * The selection of the checkbox list is taken from the value of the model attribute.
     *
     * @param array|callable $items   the data item used to generate the checkboxes.
     *                                The array values are the labels, while the array keys are the corresponding checkbox values.
     *                                Note that the labels will NOT be HTML-encoded, while the values will.
     * @param array          $options options (name => config) for the checkbox list. The following options are specially handled:
     *
     * - unselect: string, the value that should be submitted when none of the checkboxes is selected.
     *   By setting this option, a hidden input will be generated.
     * - separator: string, the HTML code that separates items.
     * - item: callable, a callback that can be used to customize the generation of the HTML code
     *   corresponding to a single item in $items. The signature of this callback must be:
     *
     * ```php
     * function ($index, $label, $name, $checked, $value)
     * ```
     *
     * where $index is the zero-based index of the checkbox in the whole list; $label
     * is the label for the checkbox; and $name, $value and $checked represent the name,
     * value and the checked status of the checkbox input.
     * @return static the field object itself
     */
    public function checkboxList($items, $options = [])
    {
        $cacheKey = $this->getCacheKey(__METHOD__);
        if (($this->parts['{input}'] = $this->cache->get($cacheKey)) !== false) {
            return $this;
        }
        if ($items instanceof \Closure) {
            $items = call_user_func($items, $this);
        }
        $this->adjustLabelFor($options);
        $this->parts['{input}'] = Html::activeCheckboxList($this->model, $this->attribute, $items, $options);
        $this->cache->set($cacheKey, $this->parts['{input}']);

        return $this;
    }

    /**
     * Renders a list of radio buttons.
     * A radio button list is like a checkbox list, except that it only allows single selection.
     * The selection of the radio buttons is taken from the value of the model attribute.
     *
     * @param array|callable $items   the data item used to generate the radio buttons.
     *                                The array keys are the labels, while the array values are the corresponding radio button values.
     *                                Note that the labels will NOT be HTML-encoded, while the values will.
     * @param array          $options options (name => config) for the radio button list. The following options are specially handled:
     *
     * - unselect: string, the value that should be submitted when none of the radio buttons is selected.
     *   By setting this option, a hidden input will be generated.
     * - separator: string, the HTML code that separates items.
     * - item: callable, a callback that can be used to customize the generation of the HTML code
     *   corresponding to a single item in $items. The signature of this callback must be:
     *
     * ```php
     * function ($index, $label, $name, $checked, $value)
     * ```
     *
     * where $index is the zero-based index of the radio button in the whole list; $label
     * is the label for the radio button; and $name, $value and $checked represent the name,
     * value and the checked status of the radio button input.
     * @return static the field object itself
     */
    public function radioList($items, $options = [])
    {
        $cacheKey = $this->getCacheKey(__METHOD__);
        if (($this->parts['{input}'] = $this->cache->get($cacheKey)) !== false) {
            return $this;
        }
        if ($items instanceof \Closure) {
            $items = call_user_func($items, $this);
        }
        $this->adjustLabelFor($options);
        if (!isset($options['itemOptions']['data-ng-model'])) {
            $options['itemOptions']['data-ng-model'] = "{$this->formName}.values.{$this->attribute}";
        }
        $this->parts['{input}'] = Html::activeRadioList($this->model, $this->attribute, $items, $options);
        $this->cache->set($cacheKey, $this->parts['{input}']);

        return $this;
    }

    /**
     * Renders a widget as the input of the field.
     *
     * Note that the widget must have both `model` and `attribute` properties. They will
     * be initialized with `model` and `attribute` of this field, respectively.
     *
     * If you want to use a widget that does not have `model` and `attribute` properties,
     * please use {@see \rock\widgets\ActiveField::render()} instead.
     *
     * For example to use the {@see \rock\widgets\Captcha} widget to get some date input, you can use
     * the following code, assuming that `$form` is your {@see \rock\widgets\ActiveForm} instance:
     *
     * ```php
     * $form->field($model, 'captcha')->widget(\rock\widgets\Captcha::className(), [
     *     'output'=> \rock\widgets\Captcha:BASE64,
     * ]);
     * ```
     *
     * @param string $class  the widget class name
     * @param array  $config name-value pairs that will be used to initialize the widget
     * @return static the field object itself
     */
    public function widget($class, $config = [])
    {
        /** @var Widget $class */
        $config['model'] = $this->model;
        $config['attribute'] = $this->attribute;
        $config['activeField'] = $this;
        //$config['view'] = $this->form->getView();
        $this->parts['{input}'] = $class::widget($config);

        return $this;
    }

    /**
     * Turns on query caching.
     * This method is provided as a shortcut to setting two properties that are related
     * with query caching: `cacheExpire` and `cacheTags`.
     *
     * @param int|null $expire
     * @param string[] $tags the tags for the cached query result.
     *                       See `cacheTags` for more details.
     *                       If not set, it will use the value of `cacheExpire`. See `cacheExpire` for more details.
     * @return $this
     */
    public function beginCache($expire = 0, array $tags = null)
    {
        $this->enableCache = true;
        if ($expire !== null) {
            $this->cacheExpire = $expire;
        }
        $this->cacheTags = $tags;

        return $this;
    }

    /**
     * Turns off query caching.
     */
    public function endCache()
    {
        $this->enableCache = false;

        return $this;
    }

    protected function getCacheKey($method)
    {
        $model = $this->model;

        return $model::className() . $this->attribute . $method;
    }
}