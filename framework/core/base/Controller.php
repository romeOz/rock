<?php
namespace rock\base;

use rock\helpers\ArrayHelper;
use rock\helpers\FileHelper;
use rock\helpers\StringHelper;
use rock\Rock;
use rock\template\Template;

abstract class Controller implements ComponentsInterface
{
    use ComponentsTrait {
        ComponentsTrait::__set as parentSet;
        ComponentsTrait::__get as parentGet;
        ComponentsTrait::init as parentInit;
    }

    /**
     * @event ActionEvent an event raised right before executing a controller action.
     * You may set {@see \rock\base\ActionEvent::$isValid} to be false to cancel the action execution.
     */
    const EVENT_BEFORE_ACTION = 'beforeAction';
    /**
     * @event ActionEvent an event raised right after executing a controller action.
     */
    const EVENT_AFTER_ACTION = 'afterAction';

    /** @var  Template */
    protected $template;

    public function init()
    {
        $this->parentInit();
        if (!isset($this->template)) {
            $this->template = Rock::$app->template;
            $this->template->context = $this;
            if (!$this->template->hasResource('context')) {
                Rock::$app->controller = $this;
                $this->template->addMultiResources(static::defaultData());
            }
        }
    }

    /**
     * Array data by context
     *
     * @return array
     */
    public static function defaultData()
    {
        return [];
    }

    /**
     * Renders a view with a layout.
     *
     *
     * @param string $layout       name of the view to be rendered.
     * @param array  $placeholders
     * @param string $defaultPathLayout
     * @return string the rendering result. Null if the rendering result is not required.
     */
    public function render($layout, array $placeholders = [],$defaultPathLayout = '@views'){

        $layout = FileHelper::normalizePath(Alias::getAlias($layout));
        if (!strstr($layout, DS)) {
            $class = explode('\\', get_class($this));
            $layout = Alias::getAlias($defaultPathLayout). DS . 'layouts' . DS .
                      strtolower(str_replace('Controller', '', array_pop($class))) . DS .
                      $layout;
        }

        echo $this->template->render($layout, $placeholders, $this);
    }

    public static function context(array $keys = [])
    {
        $keys = array_merge(['context'], $keys);
        return ArrayHelper::getValue(static::defaultData(), $keys);
    }

    /**
     * Display notPage layout
     *
     * @param string|null $layout
     * @return string|void
     */
    public function notPage($layout = null)
    {
        Rock::$app->response->status404();
        $this->template->title = StringHelper::upperFirst( Rock::t('notPage'));
        if (!isset($layout)) {
            $layout = '@common.views/layouts/notPage';
        }
        return $this->render($layout);
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
    public function beforeAction($action)
    {
        $event = new ActionEvent($action);
        $this->trigger(self::EVENT_BEFORE_ACTION, $event);
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
    public function afterAction($action, $result)
    {
        $event = new ActionEvent($action);
        $event->result = $result;
        $this->trigger(self::EVENT_AFTER_ACTION, $event);
        return $event->result;
    }

    /**
     * Get method
     *
     * @param string $actionName name of method
     * @param array  $args args action method
     * @return mixed
     * @throws ControllerException
     */
    public function method($actionName, array $args = null)
    {
        if (!method_exists($this, $actionName)) {
            $this->detachBehaviors();
            throw new ControllerException(ControllerException::UNKNOWN_METHOD, [
                'method' => get_class($this) . '::' . $actionName
            ]);
        }
        if ($this->beforeAction($actionName) === false) {
            return null;
        }
        $result = call_user_func_array([$this, $actionName], [$args]);

        if ($this->afterAction($actionName, $result) === false) {
            return null;
        }

        return $result;
    }

    public static function findUrlById($resource)
    {
        return null;
    }
}