<?php

namespace rock\filters;


use rock\access\Access;
use rock\Rock;

class AccessFilter extends ActionFilter
{
    protected $accessErrors = 0;
  //  public $only = [];
    public $rules = [];

    /**
     * ~~~~~~~~~~~~~~~
     * [[new Object, 'method'], $args]
     * [['Object', 'staticMethod'], $args]
     * [callback, $args]
     * ~~~~~~~~~~~~~~~
     *
     * @var array
     */
    public $success;

    /**
     * ~~~~~~~~~~~~~~~
     * [[new Object, 'method'], $args]
     * [['Object', 'staticMethod'], $args]
     * [callback, $args]
     * ~~~~~~~~~~~~~~~
     *
     * @var array
     */
    public $fail;

    public function before($action = null)
    {
        if (!$this->validateActions($action)) {

            return parent::before();
        }

        /** @var Access $access */
        $access = Rock::factory([
            'class' => Access::className(),
            'owner' => $this->owner,
            //'action' => $action,
            'rules' => $this->rules,
            'success' => $this->success,
            'fail' => $this->fail
        ]);
        if (!$access->checkAccess()) {
            $this->accessErrors |= $access->errors;
            return false;
        }

        return parent::before();
    }

    public function getAccessErrors()
    {
        return $this->accessErrors;
    }
}