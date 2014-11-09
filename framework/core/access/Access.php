<?php
namespace rock\access;

use rock\base\ObjectTrait;
use rock\helpers\Helper;
use rock\route\ErrorsInterface;
use rock\route\ErrorsTrait;

class Access implements ErrorsInterface
{
    use ObjectTrait;
    use ErrorsTrait;

    /**
     * @var array
     */
    public $rules = [];
    /**
     * Owner object
     *
     * @var object
     */
    public $owner;
    public $data;
    /**
     * Sending response headers. `true` by default.
     * @var bool
     */
    public $sendHeaders = true;


    /**
     * ```php
     * [[new Object, 'method'], $args]
     * [['Object', 'staticMethod'], $args]
     * [callback, $args]
     * ```
     *
     * function(array params, Access $access){}
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
     * function(array params, Access $access){}
     *
     * @var array
     */
    public $fail;

    /**
     * @var int
     */
    public $errors = 0;


    /**
     * Check Access
     *
     * @return bool
     */
    public function checkAccess()
    {
        if (empty($this->rules) || !is_array($this->rules) || empty($this->owner)) {
            return true;
        }
        if ($valid = $this->provide()) {
            $this->errors = 0;
        }
        $this->callback($valid === false ? $this->fail : $this->success);

        return $valid;
    }

    /**
     * Check Access
     *
     * @return bool
     * @throws AccessException
     */
    protected function provide()
    {
        if (!is_object($this->owner)) {
            throw new AccessException(AccessException::NOT_OBJECT, ['name' => 'owner']);
        }
        if (!isset($this->rules['allow'])) {
            return true;
        }
        if (($valid = $this->matches($this->rules)) === null) {
            return !$this->rules['allow'];
        }

        return (bool)$valid;
    }

    /**
     * Match
     *
     * @param array $rule - array data of access
     * @return bool|null
     */
    protected function matches(array $rule)
    {
        $rule['allow'] = (bool)$rule['allow'];
        $result = [];
        if (isset($rule['users'])) {
            $result[] = $this->initError($this->matchUsers((array)$rule['users']), self::E_USERS, $rule['allow']);
        }
        if (isset($rule['ips'])) {
            $result[] = $this->initError($this->matchIps((array)$rule['ips']), self::E_IPS, $rule['allow']);
        }
        if (isset($rule['verbs'])) {
            $result[] = $this->initError($this->matchVerbs((array)$rule['verbs']), self::E_VERBS, $rule['allow']);
        }
        if (isset($rule['roles'])) {
            $result[] = $this->initError($this->matchRole((array)$rule['roles']), self::E_ROLES, $rule['allow']);
        }
        if (isset($rule['custom'])) {
            $result[] = $this->initError($this->matchCustom($rule), self::E_CUSTOM, $rule['allow']);
        }
        if (empty($result)) {
            return null;
        }
        if (in_array(false, $result, true)) {
            return null;
        }

        return $rule['allow'];
    }

    /**
     * Init error
     *
     * @param bool $value
     * @param int  $error
     * @param bool     $allow
     * @return bool
     */
    protected function initError($value, $error, $allow)
    {
        if ($value === false || $allow === false) {
            $this->errors |= $error;
        }

        return $value;
    }

    /**
     * Match by users
     *
     * @param array $users array data of access
     * @return null|bool
     */
    protected function matchUsers(array $users)
    {
        // All users
        if (in_array('*', $users)) {
            return true;
        // guest
        } elseif (in_array('?', $users) && $this->Rock->user->isGuest()) {
            return true;
        // Authenticated
        } elseif (in_array('@', $users) && !$this->Rock->user->isGuest()) {
            return true;
        // username
        } elseif (in_array($this->Rock->user->get('username'), $users)) {
            return true;
        }
        if ($this->sendHeaders) {
            $this->Rock->response->status403();
        }
        return false;
    }

    /**
     * Match ips
     *
     * @param array $ips array data of access
     * @return bool
     */
    protected function matchIps(array $ips)
    {
        // all ips
        if (in_array('*', $ips)) {
            return true;
        }
        $result = $this->Rock->request->isIps($ips);
        if (!$result && $this->sendHeaders) {
            $this->Rock->response->status403();
        }
        return $result;
    }

    /**
     * Match methods by request
     *
     * @param array $verbs array data of access
     * @return bool
     */
    protected function matchVerbs(array $verbs)
    {
        // all methods by request
        if (in_array('*', $verbs)) {
            return true;
        }

        if ($this->Rock->request->isMethods($verbs)) {
            return true;
        }
        if ($this->sendHeaders) {
            $response = $this->Rock->response;
            // http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.7
            $response->getHeaders()->set('Allow', implode(', ', $verbs));
            $response->setStatusCode(405);
        }
        return false;
    }

    /**
     * Match RBAC
     *
     * @param array $roles
     * @return bool
     */
    protected function matchRole(array $roles)
    {
        // all roles
        if (in_array('*', $roles)) {

            return true;
        } elseif (in_array('?', $roles) && $this->Rock->user->isGuest()) {
            return true;
        // Authenticated
        } elseif (in_array('@', $roles) && !$this->Rock->user->isGuest()) {
            return true;
        }

        foreach ($roles as $role) {
            if (!$this->Rock->user->check($role)) {
                if ($this->sendHeaders) {
                    $this->Rock->response->status403();
                }
                return false;
            }
        }

        return true;
    }

    /**
     * Match by Custom
     *
     * @param array $rule array data of access
     * @return bool
     */
    protected function matchCustom(array $rule)
    {
        $rule['custom'][1] = Helper::getValueIsset($rule['custom'][1], []);
        list($function, $args) = $rule['custom'];

        $result = (bool)call_user_func(
            $function,
            array_merge(['owner' => $this->owner/*, 'action' => $this->action*/], $args)
        );
        if (!$result && $this->sendHeaders) {
            $this->Rock->response->status403();
        }
        return $result;
    }

    protected function callback($handler)
    {
        if (!isset($handler)) {
            return;
        }

        if ($handler instanceof \Closure) {
            $handler = [$handler];
        }
        $handler[1] = Helper::getValueIsset($handler[1], []);
        list($function, $data) = $handler;
        $access = clone $this;
        $access->data = $data;
        call_user_func($function, $access);
    }
}