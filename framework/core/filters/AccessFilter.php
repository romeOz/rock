<?php

namespace rock\filters;


use rock\access\Access;
use rock\components\ActionFilter;
use rock\core\Controller;
use rock\db\BaseActiveRecord;
use rock\di\Container;
use rock\route\Route;
use rock\route\RouteEvent;
use rock\template\Template;

/**
 * Access provides simple access control based on a set of rules.
 *
 * AccessControl is an action filter. It will check its {@see \rock\filters\AccessFilter::$rules} to find
 * the first rule that matches the current context variables (such as user IP address, user role).
 * The matching rule will dictate whether to allow or deny the access to the requested controller
 * action. If no rule matches, the access will be denied.
 *
 * To use AccessControl, declare it in the `behaviors()` method of your controller class.
 * For example, the following declarations will allow authenticated users to access the "create"
 * and "update" actions and deny all other users from accessing these two actions.
 *
 * ```php
 * public function behaviors()
 * {
 *  return [
 *   'access' => [
 *          'class' => AccessControl::className(),
 *          'only' => ['actionCreate', 'actionUpdate'],
 *          'rules' => [
 *              // deny all POST requests
 *              [
 *               'allow' => false,
 *               'verbs' => ['POST']
 *              ],
 *              // allow authenticated users
 *              [
 *                  'allow' => true,
 *                  'roles' => ['@'],
 *              ],
 *          // everything else is denied
 *          ],
 *      ],
 * ];
 * }
 * ```
 */
class AccessFilter extends ActionFilter
{
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
        $access = Container::load([
            'class' => Access::className(),
            'owner' => $this->owner,
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
}