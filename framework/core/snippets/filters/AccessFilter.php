<?php

namespace rock\snippets\filters;


use rock\access\Access;
use rock\di\Container;

class AccessFilter extends SnippetFilter
{
    /** @var  Access */
    public $access;
    public $rules = [];

    public function before()
    {
        $config = [
            'class' => Access::className(),
            'owner' => $this->owner,
            'rules' => $this->rules,
        ];
        if (class_exists('\rock\di\Container')) {
            $this->access = Container::load($config);
        } else {
            unset($config['class']);
            $this->access = new Access($config);
        }
        if (!$this->access->checkAccess()) {
            return false;
        }

        return parent::before();
    }
}