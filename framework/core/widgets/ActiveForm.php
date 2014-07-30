<?php

namespace rock\widgets;


use rock\base\ComponentsTrait;
use rock\base\Model;
use rock\base\ObjectTrait;
use rock\base\Widget;
use rock\helpers\Html;
use rock\Rock;

class ActiveForm extends Widget
{

    /**
     * @param array|string $action the form action URL. This parameter will be processed by `\rock\url\Url::getAbsoluteUrl()`.
     * @see method for specifying the HTTP method for this form.
     */
    public $action = '';
    /**
     * @var string the form submission method. This should be either 'post' or 'get'. Defaults to 'post'.
     *
     * When you set this to 'get' you may see the url parameters repeated on each request.
     * This is because the default value of [[action]] is set to be the current request url and each submit
     * will add new parameters instead of replacing existing ones.
     * You may set `action` explicitly to avoid this:
     *
     * ```php
     * $form = ActiveForm::begin([
     *     'method' => 'get',
     *     'action' => ['controller/action'],
     * ]);
     * ```
     */
    public $method = 'post';
    /**
     * @var \rock\base\Model
     */
    public $model;
    /**
     * @var array the HTML attributes (name-value pairs) for the form tag.
     * @see \rock\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    public $options = [];
    /**
     * @var array the default configuration used by `field()` when creating a new field object.
     */
    public $fieldConfig;
    /**
     * @var string the default CSS class for the error summary container.
     * @see errorSummary()
     */
    public $errorSummaryCssClass = 'error-summary';
    /**
     * @var string the CSS class that is added to a field container when the associated attribute is required.
     */
    public $requiredCssClass = 'required';
    /**
     * @var string the CSS class that is added to a field container when the associated attribute has validation error.
     */
    public $errorCssClass = 'has-error';
    /**
     * @var string the CSS class that is added to a field container when the associated attribute is successfully validated.
     */
    public $successCssClass = 'has-success';
    /**
     * @var string the CSS class that is added to a field container when the associated attribute is being validated.
     */
    public $validatingCssClass = 'validating';
    /**
     * @var boolean whether to enable client-side data validation.
     * If [[ActiveField::enableClientValidation]] is set, its value will take precedence for that input field.
     */
    public $enableClientValidation = true;
    /**
     * @var boolean whether to enable AJAX-based data validation.
     * If [[ActiveField::enableAjaxValidation]] is set, its value will take precedence for that input field.
     */
    public $enableAjaxValidation = false;
    /**
     * @var array|string the URL for performing AJAX-based validation. This property will be processed by
     * `\rock\url\Url::getAbsoluteUrl()`. Please refer to `\rock\url\Url::getAbsoluteUrl()` for more details on how to configure this property.
     * If this property is not set, it will take the value of the form's action attribute.
     */
    public $validationUrl;
    /**
     * @var boolean whether to perform validation when the form is submitted.
     */
    public $validateOnSubmit = true;
    /**
     * @var boolean whether to perform validation when an input field loses focus and its value is found changed.
     * If [[ActiveField::validateOnChange]] is set, its value will take precedence for that input field.
     */
    public $validateOnChange = true;
    /**
     * @var boolean whether to perform validation while the user is typing in an input field.
     * If [[ActiveField::validateOnType]] is set, its value will take precedence for that input field.
     * @see validationDelay
     */
    public $validateOnType = false;
    /**
     * @var integer number of milliseconds that the validation should be delayed when an input field
     * is changed or the user types in the field.
     * If [[ActiveField::validationDelay]] is set, its value will take precedence for that input field.
     */
    public $validationDelay = 200;
    /**
     * @var string the name of the GET parameter indicating the validation request is an AJAX request.
     */
    public $ajaxParam = 'ajax';
    /**
     * @var string the type of data that you're expecting back from the server.
     */
    public $ajaxDataType = 'json';
    /**
     * @var string|JsExpression a JS callback that will be called when the form is being submitted.
     * The signature of the callback should be:
     *
     * ~~~
     * function ($form) {
     *     ...return false to cancel submission...
     * }
     * ~~~
     */
    public $beforeSubmit;
    /**
     * @var string|JsExpression a JS callback that is called before validating an attribute.
     * The signature of the callback should be:
     *
     * ~~~
     * function ($form, attribute, messages) {
     *     ...return false to cancel the validation...
     * }
     * ~~~
     */
    public $beforeValidate;
    /**
     * @var string|JsExpression a JS callback that is called after validating an attribute.
     * The signature of the callback should be:
     *
     * ~~~
     * function ($form, attribute, messages) {
     * }
     * ~~~
     */
    public $afterValidate;
    /**
     * @var array the client validation options for individual attributes. Each element of the array
     * represents the validation options for a particular attribute.
     * @internal
     */
    public $attributes = [];

    /**
     * Initializes the widget.
     * This renders the form open tag.
     */
    public function init()
    {
        if (!isset($this->options['id'])) {
            $this->options['id'] = $this->getId();
        }
        if (!isset($this->fieldConfig['class'])) {
            $this->fieldConfig['class'] = ActiveField::className();
        }
        echo Html::beginForm($this->model->formName(), $this->action, $this->method, $this->options, $this->model);
    }

    /**
     * Runs the widget.
     * Renders the form close tag.
     */
    public function run()
    {
        echo Html::endForm();
    }


    /**
     * Generates a form field.
     * A form field is associated with a model and an attribute. It contains a label, an input and an error message
     * and use them to interact with end users to collect their inputs for the attribute.
     * @param \rock\base\Model $model the data model
     * @param string $attribute the attribute name or expression. See `\rock\helpers\Html::getAttributeName()` for the format
     * about attribute expression.
     * @param array $options the additional configurations for the field object
     * @return ActiveField the created ActiveField object
     * @see fieldConfig
     */
    public function field($model, $attribute, $options = [])
    {
        return Rock::factory(
            array_merge(
                $this->fieldConfig, 
                $options, [
                               'model' => $model,
                               'attribute' => $attribute,
                               'form' => $this,
                           ]
            )
        );
    }

    /**
     * Validates one or several models and returns an error message array indexed by the attribute IDs.
     * This is a helper method that simplifies the way of writing AJAX validation code.
     *
     * For example, you may use the following code in a controller action to respond
     * to an AJAX validation request:
     *
     * ```php
     * $model = new Post;
     * $model->load($_POST);
     * if (Rock::$app->request->isAjax) {
     *     Rock::$app->response->format = Response::FORMAT_JSON;
     *     return ActiveForm::validate($model);
     * }
     * // ... respond to non-AJAX request ...
     * ```
     *
     * To validate multiple models, simply pass each model as a parameter to this method, like
     * the following:
     *
     * ```php
     * ActiveForm::validate($model1, $model2, ...);
     * ```
     *
     * @param \rock\base\Model $model the model to be validated
     * @param mixed $attributes list of attributes that should be validated.
     * If this parameter is empty, it means any attribute listed in the applicable
     * validation rules should be validated.
     *
     * When this method is used to validate multiple models, this parameter will be interpreted
     * as a model.
     *
     * @return array the error message array indexed by the attribute IDs.
     */
    public static function validate($model, $attributes = null)
    {
        $result = [];
        if ($attributes instanceof Model) {
            // validating multiple models
            $models = func_get_args();
            $attributes = null;
        } else {
            $models = [$model];
        }
        /** @var \rock\base\Model $model */
        foreach ($models as $model) {
            $model->validate($attributes);
            foreach ($model->getErrors() as $attribute => $errors) {
                $result[Html::getInputId($model, $attribute)] = $errors;
            }
        }

        return $result;
    }

    /**
     * Validates an array of model instances and returns an error message array indexed by the attribute IDs.
     * This is a helper method that simplifies the way of writing AJAX validation code for tabular input.
     *
     * For example, you may use the following code in a controller action to respond
     * to an AJAX validation request:
     *
     * ```php
     * // ... load $models ...
     * if (Rock::$app->request->isAjax) {
     *     Rock::$app->response->format = Response::FORMAT_JSON;
     *     return ActiveForm::validateMultiple($models);
     * }
     * // ... respond to non-AJAX request ...
     * ```
     *
     * @param array $models an array of models to be validated.
     * @param mixed $attributes list of attributes that should be validated.
     * If this parameter is empty, it means any attribute listed in the applicable
     * validation rules should be validated.
     * @return array the error message array indexed by the attribute IDs.
     */
    public static function validateMultiple($models, $attributes = null)
    {
        $result = [];
        /** @var Model $model */
        foreach ($models as $i => $model) {
            $model->validate($attributes);
            foreach ($model->getErrors() as $attribute => $errors) {
                $result[Html::getInputId($model, "[$i]" . $attribute)] = $errors;
            }
        }

        return $result;
    }
}