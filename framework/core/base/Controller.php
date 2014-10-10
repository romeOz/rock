<?php
namespace rock\base;

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

        $layout = File::normalizePath(Rock::getAlias($layout));
        if (!strstr($layout, DS)) {
            $class = explode('\\', get_class($this));
            $layout = Rock::getAlias($defaultPathLayout). DS . 'layouts' . DS .
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
        $this->Rock->response->status404();
        $this->template->title = String::upperFirst( Rock::t('notPage'));
        if (!isset($layout)) {
            $layout = '@common.views/layouts/notPage';
        }
        return $this->render($layout);
    }

    public static function findUrlById($resource)
    {
        return null;
    }
}