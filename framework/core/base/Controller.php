<?php
namespace rock\base;

use rock\base\ComponentsTrait;
use rock\base\ObjectTrait;
use rock\helpers\ArrayHelper;
use rock\helpers\File;
use rock\helpers\String;
use rock\Rock;
use rock\template\Template;

abstract class Controller implements ComponentsInterface
{
    use ComponentsTrait {
        ComponentsTrait::__set as parentSet;
        ComponentsTrait::__get as parentGet;
        ComponentsTrait::init as parentInit;
    }


    /** @var  Template */
    protected $template;

    public function init()
    {
        $this->parentInit();
        if (!isset($this->template)) {
            $this->template = $this->Rock->template;
            if (!$this->template->hasResource('context')) {
                Rock::$app->currentController = $this;
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
    public function render($layout, array $placeholders = null,$defaultPathLayout = '@views'){

        $layout = File::normalizePath(Rock::getAlias($layout));
        if (!strstr($layout, DS)) {
            $class = explode('\\', get_class($this));
            $layout = Rock::getAlias($defaultPathLayout). DS . 'layouts' . DS .
                      strtolower(str_replace('Controller', '', array_pop($class))) . DS .
                      $layout;
        }

        echo $this->template->render($layout, $placeholders, $this);
        //return null;
    }



    final public static function context(array $keys = [])
    {
        $keys = array_merge(['context'], $keys);
        return ArrayHelper::getValue(static::defaultData(), $keys);
    }


    /**
     * Display notPage layout
     *
     * @param $layout
     * @return string|void
     */
    public function notPage($layout)
    {
        $this->Rock->response->status404();

        $content = Rock::t('notPage');
        $this->template->title = String::upperFirst($content);
        $this->render($layout, ['content' => $content]);
    }

    /**
     * Display notContent layout
     *
     * @return string|void
     */
    public function notContent()
    {
        $this->render('@common.views/layout/notContent');
    }


    public static function findUrlById($resource)
    {
        return null;
    }
}