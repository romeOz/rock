<?php
namespace rock\template;

use rock\components\ComponentsInterface;
use rock\components\ComponentsTrait;
use rock\di\Container;

abstract class Snippet implements ComponentsInterface
{
    use ComponentsTrait {
        ComponentsTrait::__set as parentSet;
        ComponentsTrait::init as parentInit;
    }
    const EVENT_BEFORE_SNIPPET = 'beforeSnippet';
    const EVENT_AFTER_SNIPPET = 'afterSnippet';

    /**
     * Is mode auto-escaping
     * @var int|bool
     */
    public $autoEscape = true;
    /** @var  Template|string|array */
    public $template = 'template';

    public function init()
    {
        $this->parentInit();

        if (!is_object($this->template)) {
            $this->template = Container::load($this->template);
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
     * The method will trigger the {@see \rock\core\Controller::EVENT_BEFORE_ACTION} event. The return value of the method
     * will determine whether the action should continue to run.
     *
     * If you override this method, your code should look like the following:
     *
     * ```php
     * public function beforeSnippet()
     * {
     *     if (parent::beforeSnippet()) {
     *         // your custom code here
     *         return true;  // or false if needed
     *     } else {
     *         return false;
     *     }
     * }
     * ```
     *
     * @return boolean whether the action should continue to run.
     */
    public function beforeSnippet()
    {
        $event = new SnippetEvent();
        $this->trigger(self::EVENT_BEFORE_SNIPPET, $event);
        return $event->isValid;
    }

    /**
     * This method is invoked right after an action is executed.
     *
     * The method will trigger the {@see \rock\core\Controller::EVENT_AFTER_ACTION} event. The return value of the method
     * will be used as the action return value.
     *
     * If you override this method, your code should look like the following:
     *
     * ```php
     * public function afterSnippet($result)
     * {
     *     $result = parent::afterSnippet($result);
     *     // your custom code here
     *     return $result;
     * }
     * ```
     *
     * @param mixed $result the action return result.
     * @return mixed the processed action result.
     */
    public function afterSnippet($result)
    {
        $event = new SnippetEvent();
        $event->result = $result;
        $this->trigger(self::EVENT_AFTER_SNIPPET, $event);
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
                if (Container::exists($function[0])) {
                    $function[0] = Container::load($function[0]);
                }
                return call_user_func_array($function, $params);
            }
        }
        return call_user_func_array($function, $params);
    }
}