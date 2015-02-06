<?php

namespace rock\snippets\filters;


use rock\access\Access;
use rock\helpers\Instance;

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
        $this->access = Instance::ensure($config, '\rock\access\Access');
        if (!$this->access->checkAccess()) {
            return false;
        }

        return parent::before();
    }
}