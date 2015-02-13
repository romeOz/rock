<?php
namespace rock\access;

use rock\base\ObjectInterface;
use rock\base\ObjectTrait;
use rock\di\Container;
use rock\helpers\Helper;
use rock\request\Request;
use rock\response\Response;
use rock\user\User;

class Access implements ErrorsInterface, ObjectInterface
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
    /**
     * Sending response headers. `true` by default.
     * @var bool
     */
    public $sendHeaders = true;
    /**
     * @var int
     */
    public $errors = 0;
    /** @var  Request */
    private $_request;
    /** @var  Response */
    private $_response;
    /** @var  User */
    private $_user;

    public function init()
    {
        $this->_request = Container::load(Request::className());
        $this->_response = Container::load(Response::className());
        $this->_user = Container::load(User::className());
    }

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
     * @param array $rule array data of access
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
        } elseif (in_array('?', $users) && $this->_user->isGuest()) {
            return true;
            // Authenticated
        } elseif (in_array('@', $users) && !$this->_user->isGuest()) {
            return true;
            // username
        } elseif (in_array($this->_user->get('username'), $users)) {
            return true;
        }
        if ($this->sendHeaders) {
            $this->_response->status403();
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
        $result = $this->_request->isIps($ips);
        if (!$result && $this->sendHeaders) {
            $this->_response->status403();
        }
        return $result;
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
        } elseif (in_array('?', $roles) && $this->_user->isGuest()) {
            return true;
            // Authenticated
        } elseif (in_array('@', $roles) && !$this->_user->isGuest()) {
            return true;
        }

        foreach ($roles as $role) {
            if (!$this->_user->check($role)) {
                if ($this->sendHeaders) {
                    $this->_response->status403();
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
        $rule['custom'][1] = Helper::getValue($rule['custom'][1], [], true);
        list($function, $args) = $rule['custom'];

        $result = (bool)call_user_func(
            $function,
            array_merge(['owner' => $this->owner/*, 'action' => $this->action*/], $args)
        );
        if (!$result && $this->sendHeaders) {
            $this->_response->status403();
        }
        return $result;
    }
}