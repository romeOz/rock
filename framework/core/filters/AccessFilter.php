<?php

namespace rock\filters;


use rock\access\Access;
use rock\base\ActionFilter;
use rock\base\Controller;
use rock\db\BaseActiveRecord;
use rock\event\Event;
use rock\Rock;
use rock\route\Route;
use rock\route\RouteEvent;
use rock\template\Template;

class AccessFilter extends ActionFilter
{
    protected $accessErrors = 0;
  //  public $only = [];
    public $rules = [];

    /**
     * Call function, when access success.
     *
     * ```php
     * [[new Object, 'method'], $args]
     * [['Object', 'staticMethod'], $args]
     * [callback, $args]
     * ```
     *
     * @var array
     */
    public $success;

    /**
     * Call function, when access fail.
     *
     * ```php
     * [[new Object, 'method'], $args]
     * [['Object', 'staticMethod'], $args]
     * [callback, $args]
     * ```
     *
     * @var array
     */
    public $fail;

    public function events()
    {
        return [
            Controller::EVENT_BEFORE_ACTION => 'beforeFilter',
            Controller::EVENT_AFTER_ACTION => 'afterFilter',
            Template::EVENT_BEFORE_TEMPLATE => 'beforeFilter',
            Template::EVENT_AFTER_TEMPLATE => 'afterFilter',
            BaseActiveRecord::EVENT_BEFORE_FIND => 'beforeFilter',
            BaseActiveRecord::EVENT_AFTER_FIND => 'afterFilter',
            BaseActiveRecord::EVENT_BEFORE_INSERT => 'beforeFilter',
            BaseActiveRecord::EVENT_AFTER_INSERT => 'afterFilter',
            BaseActiveRecord::EVENT_BEFORE_UPDATE => 'beforeFilter',
            BaseActiveRecord::EVENT_AFTER_UPDATE => 'afterFilter',
            Route::EVENT_RULE_ROUTE => 'beforeFilter',
        ];
    }

    public function beforeAction($action)
    {
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
            if ($this->event instanceof RouteEvent) {
                $this->event->errors |= $access->errors;
            }
            return false;
        }

        return parent::beforeAction($action);
    }

//    public function getAccessErrors()
//    {
//        return $this->accessErrors;
//    }
}