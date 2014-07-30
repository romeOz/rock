<?php
namespace rock\base;

use rock\base\ComponentsTrait;
use rock\base\ObjectTrait;
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
            if (strpos($function, '.')) {
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