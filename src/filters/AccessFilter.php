<?php

namespace rock\filters;


use rock\access\Access;
use rock\di\Container;

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
 *              // deny ip 127.0.0.1
 *              [
 *               'allow' => false,
 *               'ips' => ['127.0.0.1']
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
    /** @var  Access */
    public $access;
    public $rules = [];

    public function beforeAction($action)
    {
        /** @var Access $access */
        $this->access = Container::load([
            'class' => Access::className(),
            'owner' => $this->owner,
            'rules' => $this->rules,
        ]);
        if (!$this->access->checkAccess()) {
            return false;
        }

        return parent::beforeAction($action);
    }
}