<?php
namespace rock\validation\rules;

use rock\base\ComponentsTrait;
use rock\base\Model;
use rock\base\ObjectTrait;
use rock\helpers\ArrayHelper;
use rock\Rock;
use rock\validation\exceptions\AbstractNestedException;
use rock\validation\exceptions\ValidationException;
use rock\validation\Validatable;

abstract class AbstractRule implements Validatable
{
    protected $name;
    protected $template = null;
    protected $params = [];

    public static $locale = '';
    public static $translator = null;

    public function __construct()
    {
        //a constructor is required for ReflectionClass::newInstance()
    }

    public function __invoke($input)
    {
        return !is_a($this, __NAMESPACE__.'\\NotEmpty')
               && $input === '' || $this->validate($input);
    }

    /**
     * Set locale
     *
     * @param string    $locale (e.g. ru)
     * @return $this
     */
    public static function setLocale($locale)
    {
        static::$locale = $locale;
    }

    public function addOr()
    {
        $rules = func_get_args();
        array_unshift($rules, $this);

        return new OneOf($rules);
    }

    public function assert($input, $enableProvideError = true)
    {
        if ($this->__invoke($input))
            return true;
        $exception = $this->reportError($input);

        if ($enableProvideError === true) {
            $this->provideErrorOne($exception);
        }

        throw $exception;
    }

    public function check($input)
    {
        return $this->assert($input);
    }


    public function getName()
    {
        if (empty($this->name))
            preg_replace('/.*\\\/', '', get_class($this));
        return $this->name;
    }

    /**
     * @param       $input
     * @param array $extraParams
     * @return AbstractNestedException
     */
    public function reportError($input, array $extraParams=array())
    {
        $exception = $this->createException();
        $input = ValidationException::stringify($input);
        $name = $this->name ? : (!empty($input) ? "\"$input\"" : null);

        $params = array_merge(
            get_class_vars(__CLASS__), get_object_vars($this), $extraParams,
            compact('input'),
            $this->params
        );

        $exception->configure($name, $params);
        if (!is_null($this->template))
            $exception->setTemplate($this->template);
        return $exception;
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }


    public function setTemplate($template)
    {
        $this->template = $template;

        return $this;
    }

    public function setParams(array $params)
    {
        $this->params = $params;
        return $this;
    }

    /**
     * @var \rock\base\Model
     */
    protected $model;

    /**
     * @param Model $model
     * @return $this
     */
    public function setModel(Model $model = null)
    {
        $this->model  = $model;
        return $this;
    }

    protected $placeholders;

    public function setPlaceholders($placeholders)
    {
        $this->placeholders = $placeholders;
        return $this;
    }

    protected $_messages = [];
    /**
     * @param array $messages
     * @return $this
     */
    public function setMessages(array $messages)
    {
        $this->_messages = $messages;
        return $this;
    }


    /**
     * @return ValidationException
     */
    protected function createException()
    {
        $currentFQN = get_called_class();
        $locale = empty(static::$locale) ? '' :  static::$locale . '\\';
        $exceptionFQN = str_replace('\\rules\\', "\\exceptions\\{$locale}", $currentFQN);
        $exceptionFQN .= 'Exception';

        if (!class_exists($exceptionFQN)) {
            $exceptionFQN = str_replace($locale, '', $exceptionFQN);
        }
        return new $exceptionFQN;
    }

    protected function provideErrorOne(ValidationException $exception)
    {
        if ($this->model instanceof Model && $this->model->getActiveAttributeName()) {
            $this->model->addError($this->model->getActiveAttributeName(), $exception->getMessage());
        }

        if (!empty($this->placeholders) && is_string($this->placeholders)) {

            Rock::$app->template->addPlaceholder($this->placeholders, $exception->getMessage());
        }
    }

    protected function provideErrorAll(AbstractNestedException $exception)
    {
        if (empty($this->placeholders) && !$this->model instanceof Model) {
            return;
        }

        if (!$messages = $exception->findAllMessages()) {
            return;
        }
        if ($this->model instanceof Model) {
            $this->model->addMultiErrors(
                ArrayHelper::depth($messages, true) === 0 && $this->model->getActiveAttributeName()
                    ? [$this->model->getActiveAttributeName() => $messages]
                    : $messages
            );
        }

        if (empty($this->placeholders)){
            return;
        }

        $template =  Rock::$app->template;
        if (is_string($this->placeholders)) {
            $messages = current($messages);
            $template->addPlaceholder(
                $this->placeholders,
                is_array($messages) ? current($messages) : $messages,
                true
            );
            return;
        }

        foreach ($this->placeholders as $key => $placeholder) {
            if (is_int($key)) {
                $key = $placeholder;
                $placeholder = 'e_' . explode('.', $placeholder)[0];
                //$this->placeholders[$key] = $placeholder;
            }

            if ($msg = $this->preparePlaceholders(explode('.', $key), $messages)) {
                $template->addPlaceholder($placeholder, $msg, true);
            }
        }
    }

    protected function preparePlaceholders(array $keys, array $messages)
    {
        if (empty($messages)) {
            return null;
        }
        $endKey = isset($keys[1]) ? $keys[1] : null;
        if ($endKey === 'first') {
            if (isset($messages[$keys[0]])) {
                return is_array($messages[$keys[0]]) ? current($messages[$keys[0]]) : $messages[$keys[0]];
            }

            return null;
        }

        if ($endKey === 'last') {
            if (isset($messages[$keys[0]])) {
                return is_array($messages[$keys[0]]) ? end($messages[$keys[0]]) : $messages[$keys[0]];
            }
            return null;
        }
        return ArrayHelper::getValue($messages, $keys);
    }

    protected function validateWithModelOrPlaceholder($input)
    {
        try {
            $this->assert($input);
        } catch (ValidationException $e){
            return false;
        }

        return true;
    }
}

