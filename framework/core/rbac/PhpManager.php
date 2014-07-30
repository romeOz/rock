<?php

namespace rock\rbac;

use rock\base\ObjectTrait;
use rock\helpers\Helper;
use rock\Rock;

class PhpManager extends RBAC
{
    protected static $items = [];
    protected static $assignments = [];
    /**
     * @var string the path of the PHP script that contains the authorization data.
     * This can be either a file path or a path alias to the file.
     * Make sure this file is writable by the Web server process if the authorization needs to be changed online.
     */
    public static $path = '@common/data/rbac.php';
    public static $pathAssignments = '@common/data/assignments.php';

    public function init()
    {
        static::$path = Rock::getAlias(static::$path);
        static::$pathAssignments = Rock::getAlias(static::$pathAssignments);

        if (empty(static::$items)) {
            static::$items = $this->load(static::$path);
        }
        if (empty(static::$assignments)) {
            static::$assignments = $this->load(static::$pathAssignments);
        }
    }

    protected function load($path)
    {
        if (!file_exists($path) || (!$data = require($path)) || !is_array($data)) {
            throw new Exception(Exception::CRITICAL, Exception::UNKNOWN_FILE, ['path' => $path]);
        }

        return $data;
    }


    /**
     * Saves the authorization data to a PHP script file.
     * @param array $data the authorization data
     * @param string $file the file path.
     * @see loadFromFile()
     */
    protected function saveToFile($data, $file)
    {
        file_put_contents($file, "<?php\nreturn " . var_export($data, true) . ";\n", LOCK_EX);
    }


    protected function processData($itemName)
    {
        if (empty(static::$items[$itemName]['data'])) {
            throw new Exception(Exception::CRITICAL, Exception::NOT_DATA_PARAMS);
        }
        $data = static::$items[$itemName]['data'];
        if (is_string($data)) {
            $data = new $data;
        }

        if ($data instanceof Item) {
            $data->name = $itemName;
            return $data;
        }

        throw new Exception(Exception::CRITICAL, Exception::UNKNOWN_TYPE, ['name' => serialize($data)]);
    }


    /**
     * @inheritdoc
     */
    public function add(Item $item)
    {
        if ($this->has($item->name)) {
            throw new Exception(Exception::CRITICAL, "Cannot add '{$item->name}'. A has been exists.");
        }
        /** @var Role|Permission  $item */
        static::$items[$item->name] = [
            'type' => $item->type,
            'description' => $item->description,
            'data' => $item->className(),
        ];

        $this->saveToFile(static::$items, static::$path);
        return true;
    }

    /**
     * @inheritdoc
     */
    public function attachItem(Role $role, Item $item)
    {
        if ($this->detect($role, $item)) {
            throw new Exception(Exception::CRITICAL, "Cannot attach '{$role->name}' as a item of '{$item->name}'. A has been detected.");
        }

        static::$items[$role->name]['items'] = Helper::getValueIsset(static::$items[$role->name]['items'], []);
        static::$items[$role->name]['items'][] = $item->name;
        $this->saveToFile(static::$items, static::$path);
        unset(static::$roles[$role->name], static::$permissions[$role->name]);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function attachItems(Role $role, array $items)
    {
        $names = [];
        foreach ($items as $item) {
            if ($this->detect($role, $item)) {
                throw new Exception(Exception::CRITICAL, "Cannot attach '{$role->name}' as a item of '{$item->name}'. A has been detected.");
            }
            $names[] = $item->name;
        }

        static::$items[$role->name]['items'] =
            array_merge(Helper::getValueIsset(static::$items[$role->name]['items'], []), $names);
        $this->saveToFile(static::$items, static::$path);
        unset(static::$roles[$role->name], static::$permissions[$role->name]);

        return true;
    }


    /**
     * @inheritdoc
     */
    public function detachItem(Role $role, Item $item)
    {
        if (!empty(static::$items[$role->name]['items'])) {
            static::$items[$role->name]['items'] = array_diff(static::$items[$role->name]['items'], [$item->name]);
            $this->saveToFile(static::$items, static::$path);
            unset(static::$roles[$role->name], static::$permissions[$role->name]);
        }

        return true;
    }

    /**
     * Detach an items
     *
     * @param Role $role
     * @param Item[]                         $items
     * @return bool
     */
    public function detachItems(Role $role, array $items)
    {
        $names = [];
        foreach ($items as $item) {
            $names[] = $item->name;
        }
        static::$items[$role->name]['items'] = array_diff(static::$items[$role->name]['items'], $names);
        $this->saveToFile(static::$items, static::$path);
        unset(static::$roles[$role->name], static::$permissions[$role->name]);
        return true;
    }


    public function remove($itemName)
    {
        $this->detachLoop($itemName);
        unset(static::$items[$itemName], static::$roles[$itemName],static::$permissions[$itemName]);
        $this->saveToFile(static::$items, static::$path);
        return true;
    }

    public function removeMulti(array $itemNames)
    {
        foreach ($itemNames as $name) {
            $this->detachLoop($name);
            unset(static::$items[$name], static::$roles[$name], static::$permissions[$name]);
        }

        $this->saveToFile(static::$items, static::$path);
        return true;
    }

    public function removeAll()
    {
        static::$items = static::$roles = static::$permissions =  null;
        $this->saveToFile([], static::$path);
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getAssignments($userId)
    {
        return isset(static::$assignments[$userId]) ? static::$assignments[$userId] : [];
    }

    /**
     * Assigns a role to a user.
     *
     * @param string|integer $userId the user ID (see [[User::id]])
     * @param Role[]         $roles  the rule to be associated with this assignment. If not null, the rule
     *                               will be executed when [[allow()]] is called to check the user permission.
     * @return bool
     * @throws Exception if the role has already been assigned to the user
     */
    public function assign($userId, array $roles)
    {
        $rows = [];

        foreach ($roles as $role) {
            if (!$role instanceof Role) {
                throw new Exception(Exception::CRITICAL, Exception::UNKNOWN_TYPE, ['name' => serialize($role)]);
            }
            if ($this->hasAssigned($userId, $role->name)) {
                throw new Exception(Exception::ERROR, "Duplicate role: {$role->name}");
            }
            $rows[] =[$userId, $role->name];
            static::$assignments[$userId][] = $role->name;
        }

        static::$assignments[$userId] = array_unique(static::$assignments[$userId]);
        $this->saveToFile(static::$assignments, static::$pathAssignments);
        return true;
    }

    /**
     * Revokes a role from a user.
     *
     * @param string|integer $userId the user ID (see [[User::id]]).
     * @param Role[]         $roles  the rule to be associated with this assignment. If not null, the rule
     * @return boolean whether the revoking is successful
     */
    public function revoke($userId, array $roles)
    {
        $names = [];
        foreach ($roles as $role) {
            if (!$role instanceof Role) {
                throw new Exception(Exception::CRITICAL, Exception::UNKNOWN_TYPE, ['name' => serialize($role)]);
            }
            $names[] = $role->name;
        }
        if (!(static::$assignments[$userId] = array_diff(static::$assignments[$userId],$names))) {
            unset(static::$assignments[$userId]);
        }

        $this->saveToFile(static::$assignments, static::$pathAssignments);
        return true;
    }

    /**
     * Revokes all roles from a user.
     *
     * @param mixed $userId the user ID (see [[User::id]])
     * @return boolean whether the revoking is successful
     */
    public function revokeAll($userId)
    {
        unset(static::$assignments[$userId]);
        $this->saveToFile([], static::$pathAssignments);
        return true;
    }

    public function refresh()
    {
        parent::refresh();
        $this->init();
    }

    protected function detachLoop($itemName)
    {
        if (empty(static::$items)) {
            return;
        }
        $names = array_flip(array_keys(static::$items));
        unset($names[$itemName]);
        if ($item = $this->get($itemName)) {
            $roles = [];
            foreach ($names as $name => $value) {
                if (($role = $this->get($name)) && $role instanceof Role) {
                    $this->detachItem($role, $item);
                    $roles[] = $role;
                }
            }

            if ($item instanceof Role) {
                foreach (array_keys(static::$assignments) as $userId) {
                    $this->revoke($userId, [$item]);
                }
            }

        }
    }
}