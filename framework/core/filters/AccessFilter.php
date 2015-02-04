<?php

namespace rock\filters;


use rock\access\Access;
use rock\components\ActionFilter;
use rock\core\Controller;
use rock\db\BaseActiveRecord;
use rock\di\Container;
use rock\route\Route;
use rock\route\RouteEvent;
use rock\template\Snippet;

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

    public function events()
    {
        $events = [];
        if (class_exists('\rock\core\Controller')) {
            $events[Controller::EVENT_BEFORE_ACTION] = 'beforeFilter';
            $events[Controller::EVENT_AFTER_ACTION] = 'afterFilter';
        }
        if (class_exists('\rock\template\Snippet')) {
            $events[Snippet::EVENT_BEFORE_SNIPPET] = 'beforeFilter';
            $events[Snippet::EVENT_AFTER_SNIPPET] = 'afterFilter';
        }

        if (class_exists('\rock\db\BaseActiveRecord')) {
            $events[BaseActiveRecord::EVENT_BEFORE_INSERT] =
            $events[BaseActiveRecord::EVENT_BEFORE_UPDATE] =
            $events[BaseActiveRecord::EVENT_BEFORE_FIND] = 'beforeFilter';
            $events[BaseActiveRecord::EVENT_AFTER_INSERT] =
            $events[BaseActiveRecord::EVENT_AFTER_UPDATE] =
            $events[BaseActiveRecord::EVENT_AFTER_FIND] = 'afterFilter';
        }

        if (class_exists('\rock\route\Route')) {
            $events[Route::EVENT_RULE_ROUTE] = 'beforeFilter';
        }
        return $events;
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