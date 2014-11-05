<?php
namespace rock\base;

use rock\di\Container;
use rock\Rock;
use rock\template\Template;

abstract class Snippet implements ComponentsInterface
{
    use ComponentsTrait {
        ComponentsTrait::__set as parentSet;
        ComponentsTrait::init as parentInit;
    }

    /**
     * Is mode auto-escaping
     * @var int|bool
     */
    public $autoEscape = true;
    /** @var  Template */
    public $template;


    public function init()
    {
        $this->parentInit();

        if (!isset($this->template)) {
            $this->template = $this->Rock->template;
        }
    }

    /**
     * Get content
     *
     * @return mixed
     */
    public function get()
    {
        return null;
    }

    /**
     * This method is invoked right before an action is executed.
     *
     * The method will trigger the {@see \rock\base\Controller::EVENT_BEFORE_ACTION} event. The return value of the method
     * will determine whether the action should continue to run.
     *
     * If you override this method, your code should look like the following:
     *
     * ```php
     * public function beforeAction($action)
     * {
     *     if (parent::beforeAction($action)) {
     *         // your custom code here
     *         return true;  // or false if needed
     *     } else {
     *         return false;
     *     }
     * }
     * ```
     *
     * @param string $action the action to be executed.
     * @return boolean whether the action should continue to run.
     */
    public function beforeSnippet($action)
    {
        $event = new ActionEvent($action);
        $this->trigger(Template::EVENT_BEFORE_TEMPLATE, $event);
        return $event->isValid;
    }

    /**
     * This method is invoked right after an action is executed.
     *
     * The method will trigger the {@see \rock\base\Controller::EVENT_AFTER_ACTION} event. The return value of the method
     * will be used as the action return value.
     *
     * If you override this method, your code should look like the following:
     *
     * ```php
     * public function afterAction($action, $result)
     * {
     *     $result = parent::afterAction($action, $result);
     *     // your custom code here
     *     return $result;
     * }
     * ```
     *
     * @param string $action the action just executed.
     * @param mixed $result the action return result.
     * @return mixed the processed action result.
     */
    public function afterSnippet($action, $result)
    {
        $event = new ActionEvent($action);
        $event->result = $result;
        $this->trigger(Template::EVENT_AFTER_TEMPLATE, $event);
        return $event->result;
    }

    /**
     * @param mixed $function - may be a callable, snippet, and instance
     * @param array $params
     * @return mixed
     *
     * ```php
     * $this->callFunction('\foo\Snippet');
     * $this->callFunction('\foo\FooController.get');
     * $this->callFunction(function{}());
     * $this->callFunction([Foo::className(), 'get']);
     * $this->callFunction([new Foo(), 'get']);
     * ```
     */
    protected function callFunction($function, array $params = [])
    {
        if (is_string($function)) {
            $function = trim($function);
            if (strpos($function, '.') !== false) {
                $function = explode('.', $function);
            } else {
                return $this->template->getSnippet($function, $params);
            }
        }
        if (is_array($function)) {
            if ($function[0] === 'context') {
                $function[0] = $this->template->context;
                return call_user_func_array($function, $params);
            } elseif (is_string($function[0])) {
                if (Container::has($function[0])) {
                    $function[0] = Rock::factory($function[0]);
                }
                return call_user_func_array($function, $params);
            }
        }
        return call_user_func_array($function, $params);
    }
}