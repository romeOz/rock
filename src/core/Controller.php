<?php
namespace rock\core;

use rock\base\Alias;
use rock\components\ComponentsInterface;
use rock\components\ComponentsTrait;
use rock\helpers\FileHelper;
use rock\helpers\Instance;
use rock\helpers\StringHelper;
use rock\i18n\i18n;
use rock\response\Response;
use rock\Rock;
use rock\template\Template;
use rock\url\Url;

/**
 * Controller.
 *
 * @property \rock\template\Template $template
 */
abstract class Controller implements ComponentsInterface
{
    use ComponentsTrait {
        ComponentsTrait::__set as parentSet;
        ComponentsTrait::__get as parentGet;
        ComponentsTrait::init as parentInit;
    }

    /**
     * @event ActionEvent an event raised right before executing a controller action.
     * You may set {@see \rock\core\ActionEvent::$isValid} to be false to cancel the action execution.
     */
    const EVENT_BEFORE_ACTION = 'beforeAction';
    /**
     * @event ActionEvent an event raised right after executing a controller action.
     */
    const EVENT_AFTER_ACTION = 'afterAction';

    /** @var  Response */
    public $response;
    /** @var  Template|string|array */
    private $_template = 'template';

    public function init()
    {
        $this->parentInit();
        Rock::$app->controller = $this;
    }

    /**
     * Returns instance {@see \rock\template\Template}.
     * @return Template
     * @throws \rock\helpers\InstanceException
     */
    public function getTemplate()
    {
        if ($this->_template instanceof Template) {
            return $this->_template;
        }
        $this->_template = Instance::ensure($this->_template);
        $this->_template->context = $this;
        return $this->_template;
    }

    /**
     * Renders a view with a layout.
     * @param string $layout name of the view to be rendered.
     * @param array $placeholders list placeholders.
     * @param string $defaultPathLayout
     * @param bool $isAjax
     * @return string the rendering result. Null if the rendering result is not required.
     * @throws \Exception
     */
    public function render($layout, array $placeholders = [], $defaultPathLayout = '@views', $isAjax = false)
    {
        $layout = FileHelper::normalizePath(Alias::getAlias($layout));
        if (!strstr($layout, DS)) {
            $class = explode('\\', get_class($this));
            $layout = Alias::getAlias($defaultPathLayout). DS . 'layouts' . DS .
                strtolower(str_replace('Controller', '', array_pop($class))) . DS .
                $layout;
        }
        return $this->getTemplate()->render($layout, $placeholders, $this, $isAjax);
    }

    /**
     * Renders a ajax-view with a layout.
     * @param string $layout name of the view to be rendered.
     * @param array $placeholders list placeholders.
     * @param string $defaultPathLayout
     * @return string
     */
    public function renderAjax($layout, array $placeholders = [], $defaultPathLayout = '@views')
    {
        return $this->render($layout, $placeholders, $defaultPathLayout, true);
    }

    /**
     * Display notPage layout
     *
     * @param string|null $layout
     * @param array $placeholders list placeholders
     * @return string|void
     */
    public function notPage($layout = null, array $placeholders = [])
    {
        if (isset($this->response)) {
            $this->response->status404();
        }
        $this->getTemplate()->title = StringHelper::upperFirst(i18n::t('notPage'));
        if (!isset($layout)) {
            $layout = '@common.views/layouts/notPage';
        }
        return $this->render($layout, $placeholders);
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
     * @return bool whether the action should continue to run.
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
     * The method will trigger the {@see \rock\core\Controller::EVENT_AFTER_ACTION} event. The return value of the method
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
     * @return mixed
     * @throws ControllerException
     */
    public function method($actionName)
    {
        $args = array_slice(func_get_args(), 1) ? : [];
        if (!method_exists($this, $actionName)) {
            $this->detachBehaviors();
            throw new ControllerException(ControllerException::UNKNOWN_METHOD, [
                'method' => get_class($this) . '::' . $actionName
            ]);
        }
        if ($this->beforeAction($actionName) === false) {
            return null;
        }
        $result = call_user_func_array([$this, $actionName], $args);//$this->$actionName($route);
        return $this->afterAction($actionName, $result);
    }

    /**
     * Redirects the browser to the specified URL.
     * This method is a shortcut to {@see \rock\response\Response::redirect()}.
     *
     * You can use it in an action by returning the {@see \rock\response\Response} directly:
     *
     * ```php
     * // stop executing this action and redirect to login page
     * return $this->redirect(['login']);
     * ```
     *
     * @param string|array $url the URL to be redirected to. This can be in one of the following formats:
     *
     * - a string representing a URL (e.g. "http://example.com")
     * - a string representing a URL alias (e.g. "@example.com")
     *
     * Any relative URL will be converted into an absolute one by prepending it with the host info
     * of the current request.
     *
     * @param integer $statusCode the HTTP status code. Defaults to 302.
     * See <http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html>
     * for details about HTTP status code
     * @return Response the current response object
     */
    public function redirect($url, $statusCode = 302)
    {
        return $this->response->redirect(Url::modify($url, Url::ABS), $statusCode);
    }

    /**
     * Redirects the browser to the home page.
     *
     * You can use this method in an action by returning the {@see \rock\response\Response} directly:
     *
     * ```php
     * // stop executing this action and redirect to home page
     * return $this->goHome();
     * ```
     *
     * @return Response the current response object
     * @throws \Exception
     */
    public function goHome()
    {
        return $this->response->redirect(Rock::$app->request->getHomeUrl());
    }

    /**
     * Redirects the browser to the last visited page.
     *
     * You can use this method in an action by returning the {@see \rock\response\Response} directly:
     *
     * ```php
     * // stop executing this action and redirect to last visited page
     * return $this->goBack();
     * ```
     *
     * For this function to work you have to  {@see \rock\user\User::setReturnUrl()} in appropriate places before.
     *
     * @param string|array $defaultUrl the default return URL in case it was not set previously.
     * If this is null and the return URL was not set previously, {@see \rock\request\Request::$homeUrl} will be redirected to.
     * Please refer to {@see \rock\user\User::setReturnUrl()} on accepted format of the URL.
     * @return Response the current response object
     * @see User::getReturnUrl()
     */
    public function goBack($defaultUrl = null)
    {
        return $this->response->redirect(Rock::$app->user->getReturnUrl($defaultUrl));
    }
}