<?php

namespace rock\snippets\html;


use rock\base\Alias;
use rock\file\UploadedFile;
use rock\helpers\Helper;
use rock\helpers\Instance;
use rock\helpers\Serialize;
use rock\request\Request;
use rock\snippets\filters\RateLimiter;
use rock\snippets\Snippet;
use rock\template\Html;

class ActiveForm extends Snippet
{
    public $config = [];
    /** @var  \rock\components\Model|string|array */
    public $model;
    public $fields = [];
    public $submitButton = [];
    public $load;
    public $prepareAttributes = [];
    public $validate = false;
    public $after;
    public $submitted = false;
    public $success;
    /**
     * Name/inline wrapper template
     *
     * @var string
     */
    public $wrapperTpl;
    /**
     * Result to placeholder (name of placeholder).
     *
     * @var string
     */
    public $toPlaceholder;
    /**
     * @inheritdoc
     */
    public $autoEscape = false;
    /** @var  RateLimiter|string|array */
    public $rateLimiter;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->rateLimiter = Instance::ensure($this->rateLimiter, '\rock\snippets\filters\RateLimiter');
    }

    /**
     * @inheritdoc
     */
    public function get()
    {
        if (empty($this->model)) {
            return null;
        }
        $this->unserialize();
        if (!is_object($this->model)) {
            if (is_string($this->model)) {
                $this->model = ['class' => Alias::getAlias($this->model)];
            }
            $this->model = Instance::ensure($this->model);
        }
        $this->model->setTemplate($this->template);

        $this->calculateLoad();
        if ($this->validate === true) {
            $this->prepareAttributes();
            if ($this->model->validate()) {
                // remove rate limiter of attributes
//                foreach ($this->fields as $attributeName => $params) {
//                    $this->rateLimiter->removeAllowance(get_class($this->model) . '::' . $attributeName);
//                }
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
                if ($this->success) {
                    return $this->template->replaceByPrefix($this->success);
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
        // Inserting content into wrapper template (optional)
        if (!empty($this->wrapperTpl)) {
            $result = $this->parseWrapperTpl($result, $this->wrapperTpl);
        }
        // To placeholder
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
                $result[] = $this->template->replaceByPrefix($params);
                continue;
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
        if (!isset($submitOptions['data-ng-disabled'])) {
            $submitOptions['data-ng-disabled'] = 'sending';
        }
        if (isset($submitOptions['wrapperTpl'])) {
            $wrapper = $submitOptions['wrapperTpl'];
            unset($submitOptions['wrapperTpl']);
            return $this->parseWrapperTpl(Html::submitButton($this->template->replace($submitContent), $submitOptions), $wrapper);
        }
        return Html::submitButton($this->template->replace($submitContent), $submitOptions);
    }

    /**
     * Inserting content into wrapper template.
     *
     * @param string $value content
     * @param        $wrapperTpl
     * @return string
     */
    protected  function parseWrapperTpl($value, $wrapperTpl)
    {
        $value = $this->template->replaceByPrefix($wrapperTpl, ['output' => $value]);
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
        if (is_string($this->model)) {
            $this->model = Serialize::unserialize($this->model, false);
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
                    $this->load = Request::getAll();
                } else {
                    $this->load = Request::postAll();
                }
            } else {
                $this->load = Request::postAll();
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