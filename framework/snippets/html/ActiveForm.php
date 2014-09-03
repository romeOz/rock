<?php

namespace rock\snippets\html;


use rock\base\Model;
use rock\base\Snippet;
use rock\file\UploadedFile;
use rock\filters\RateLimiter;
use rock\helpers\Helper;
use rock\helpers\Html;
use rock\helpers\Serialize;
use rock\request\Request;
use rock\Rock;

class ActiveForm extends Snippet
{
    public $config = [];
    /** @var  Model */
    public $model;
    public $configModel = [];

    public $fields = [];
    public $submitButton = [];

    public $load;
    public $prepareAttributes = [];
    public $validate = false;
    public $after;
    public $submitted = false;
    /**
     * name of wrapper template
     *
     * @var string
     */
    public $wrapperTpl;

    /**
     * result to placeholder (name of placeholder)
     *
     * @var string
     */
    public $toPlaceholder;

    /**
     * @var int|bool|null
     */
    public $autoEscape = false;

    public function get()
    {
        if (empty($this->model)) {
            return null;
        }
        $this->unserialize();
        if (is_string($this->model)) {
            $configModel = array_merge(['class' => Rock::getAlias($this->model)], $this->configModel);
            $this->model = Rock::factory($configModel);
        }

        $this->calculateLoad();
        if ($this->validate === true) {
            $this->prepareAttributes();
            if ($this->model->validate()) {
                /** remove rate limiter of attributes */
                foreach ($this->fields as $attributeName => $params) {
                    if (is_int($attributeName)) {
                        continue;
                    }
                    if (isset($params['options']['rateLimiter'])) {
                        $this->Rock->user->removeAllowance(get_class($this->model) . '::' . $attributeName);
                    }
                }
                if (isset($this->after)) {
                    foreach ($this->after as $method) {
                        $methodName = $method;
                        $args = null;
                        if (is_array($method)) {
                            list($methodName, $args) = $method;
                        }
                        call_user_func([$this->model, $methodName], $args);
                    }
                }
            }
        }

        $this->config['model'] = $this->model;

        ob_start();
        $form = \rock\widgets\ActiveForm::begin($this->config);
        $output = ob_get_clean();
        $fields = [];
        $fields[] = $output;
        if (!empty($this->fields)) {
            $this->prepareFields($form, (array)$this->fields, $fields);
        }

        $fields[] = $this->prepareSubmitButton($this->submitButton);
        ob_start();
        \rock\widgets\ActiveForm::end();
        $output = ob_get_clean();
        $fields[] = $output;
        $result = implode("\n", $fields);
        /**
         * Inserting content into wrapper template (optional)
         */
        if (!empty($this->wrapperTpl)) {
            $result = $this->parseWrapperTpl($result, $this->wrapperTpl);
        }

        /**
         * To placeholder
         */
        if (!empty($this->toPlaceholder)) {
            $this->template->addPlaceholder($this->toPlaceholder, $result, true);
            $this->template->cachePlaceholders[$this->toPlaceholder] = $result;
            return null;
        }

        return $result;
    }


    protected function prepareFields(\rock\widgets\ActiveForm $form, array $fields, array &$result)
    {
        $form->submitted = $this->submitted;
        foreach ($fields as $attributeName => $params) {
            if (is_int($attributeName)) {
                $attributeName = $params;
                $params = [];
            }
            if (isset($params['options']['enabled']) && $params['options']['enabled']=== false) {
                continue;
            }
            unset($params['options']['enabled']);
            $field = $form->field($this->model, $attributeName, Helper::getValue($params['options'],[]));
            unset($params['options']);

            foreach ($params as $additionName => $additionParams) {
                if (is_int($additionName)) {
                    $additionName = $additionParams;
                    unset($additionParams);
                }
                call_user_func_array([$field, $additionName], Helper::getValue($additionParams, []));
            }

            $result[] = $field->render();
        }
        return $result;
    }

    protected function prepareSubmitButton($submit)
    {
        if (empty($submit)) {
            return Html::submitButton();
        }
        $submit = (array)$submit;
        $submit[1] = isset($submit[1]) ? $submit[1] : [];
        list($submitContent, $submitOptions) = $submit;
        if (isset($submitOptions['wrapperTpl'])) {
            $wrapper = $submitOptions['wrapperTpl'];
            unset($submitOptions['wrapperTpl']);
            return $this->parseWrapperTpl(Html::submitButton($this->template->replace($submitContent), $submitOptions), $wrapper);
        }
        return Html::submitButton($this->template->replace($submitContent), $submitOptions);
    }

    /**
     * Inserting content into wrapper template
     *
     * @param string $value - content
     * @param        $wrapperTpl
     * @return string
     */
    protected  function parseWrapperTpl($value, $wrapperTpl)
    {
        $value = $this->template->replaceParamByPrefix($wrapperTpl, ['output' => $value]);
        $this->template->removePlaceholder('output');

        return $value;
    }


    protected function unserialize()
    {
        if (is_string($this->after)) {
            $this->after = Serialize::unserialize($this->after);
        }
        if (is_string($this->prepareAttributes)) {
            $this->prepareAttributes = Serialize::unserialize($this->prepareAttributes);
        }
        if (is_string($this->config)) {
            $this->config = Serialize::unserialize($this->config);
        }
        if (is_string($this->configModel)) {
            $this->configModel = Serialize::unserialize($this->configModel);
        }

        if (is_string($this->fields)) {
            $this->fields = Serialize::unserialize($this->fields);
        }

        if (is_string($this->submitButton)) {
            $this->submitButton = Serialize::unserialize($this->submitButton);
        }
    }

    protected function calculateLoad()
    {
        if (!isset($this->load)) {
            if (isset($this->config['method'])) {
                $verb = strtoupper($this->config['method']);
                if ($verb == Request::GET) {
                    $this->load = Rock::$app->request->getAll();
                } else {
                    $this->load = Rock::$app->request->postAll();
                }
            } else {
                $this->load = Rock::$app->request->postAll();
            }
        }
        if (!empty($this->load)) {
            $this->model->load($this->load);
            $this->submitted = true;
            return;
        }

        $this->validate = false;
    }



    protected function prepareAttributes()
    {
        foreach ($this->prepareAttributes as $attributeName => $value) {
            if (isset($this->model->$attributeName)) {
                if ($value == 'file') {
                    $this->model->$attributeName = UploadedFile::getInstance($this->model, $attributeName);
                } elseif ($value == 'multiFiles') {
                    $this->model->$attributeName = UploadedFile::getInstances($this->model, $attributeName);
                }
            }
        }
    }
} 