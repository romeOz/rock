<?php

namespace rock\rbac;

use apps\common\models\users\access\Items;
use apps\common\models\users\access\Roles;
use apps\common\models\users\access\RolesItems;
use apps\common\models\users\access\UsersItems;
use rock\db\Connection;
use rock\db\SelectBuilder;
use rock\helpers\ArrayHelper;
use rock\helpers\Helper;
use rock\Rock;

class DBManager extends RBAC
{

    /**
     * @var Connection|string the DB connection object or the application component ID of the DB connection.
     * After the DbManager object is created, if you want to change this property, you should only assign it
     * with a DB connection object.
     */
    public static $connection = 'db';

    protected static $items = [];
    protected static $assignments = [];

    //protected static $oldData;

    public function init()
    {
        $this->load();
    }

    protected function load()
    {
        if (!empty(static::$items)) {
            return;
        }

        if (is_string(static::$connection)) {
            static::$connection = Rock::factory(static::$connection);
        }

        Items::$connection = Roles::$connection = RolesItems::$connection = UsersItems::$connection = static::$connection;

        if (!$dataItems = Items::find()
            ->fields()
            ->sortByMenuIndex()
            ->indexBy('name')
            ->beginCache()
            ->asArray()
            ->all(static::$connection)) {

            throw new RBACException('Items is empty.');
        }
        static::$items = $dataItems;
        if (!$dataRolesItems = RolesItems::find()
            ->select(SelectBuilder::selects([Roles::find()->fields(), [Items::find()->fields(), 'items']]))
            ->innerJoinWith(
                ['items', 'roles'],
                false
            )
            ->beginCache()
            ->asArray()
            ->all(static::$connection, true)
        ) {
            return;
        }
        $result = [];
        foreach ($dataRolesItems as $value) {
            if (isset($result[$value['name']])) {
                $result[$value['name']]['items'] =
                    array_merge($result[$value['name']]['items'], (array)$value['items']['name']);
                continue;
            }
            $value['items'] = [$value['items']['name']];
            $result[$value['name']] = $value;
        }
        static::$items = ArrayHelper::toType(
            ArrayHelper::filterRecursive(
                $result + static::$items,
                function ($value, $key) {
                    return !in_array($key, ['name'], true);
                }
            )
        );
    }

    /**
     * @inheritdoc
     */
    public function add(Item $item)
    {
        if ($this->has($item->name)) {
            throw new RBACException("Cannot add '{$item->name}'. A has been exists.");
        }
        /** @var Role|Permission  $item */
        $items = new Items();
        $items->name = $item->name;
        $items->type = $item->type;
        $items->description = isset($item->description) ? $item->description : '';
        $items->data = $item->data === null ? '' : serialize($item->data);
        if (!$items->save()) {
            return false;
        }
        static::$items[$item->name] = [
            'type' => $item->type,
            'description' => $item->description,
            'data' => $item,
        ];

        return true;
    }

    /**
     * @inheritdoc
     */
    public function attachItem(Role $role, Item $item)
    {
        if ($this->detect($role, $item)) {
            throw new RBACException("Cannot attach '{$role->name}' as a item of '{$item->name}'. A has been detected.");
        }

        $result = static::$connection->createCommand()
            ->insert(RolesItems::tableName(), ['role' => $role->name, 'item' => $item->name])
            ->execute();

        static::$items[$role->name]['items'] = Helper::getValueIsset(static::$items[$role->name]['items'], []);
        static::$items[$role->name]['items'][] = $item->name;
        unset(static::$roles[$role->name], static::$permissions[$role->name]);

        return (bool)$result;
    }


    /**
     * @inheritdoc
     */
    public function attachItems(Role $role, array $items)
    {
        $rows = [];
        $itemNames = [];
        foreach ($items as $item) {
            if ($this->detect($role, $item)) {
                throw new RBACException("Cannot attach '{$role->name}' as a item of '{$item->name}'. A has been detected.");
            }
            $rows[] = [$role->name, $item->name];
            $itemNames[] = $item->name;
        }
        $result = static::$connection
            ->createCommand()
            ->batchInsert(RolesItems::tableName(), ['role', 'item'], $rows)
            ->execute();
        static::$items[$role->name]['items'] =
            array_merge(Helper::getValueIsset(static::$items[$role->name]['items'], []), $itemNames);
        unset(static::$roles[$role->name], static::$permissions[$role->name]);

        return (bool)$result;
    }


    /**
     * @inheritdoc
     */
    public function detachItem(Role $role, Item $item)
    {
        $result = true;
        if (!empty(static::$items[$role->name]['items'])) {
            $result = RolesItems::deleteAll(RolesItems::find()->byRole($role->name)->byItem($item->name)->where);
            static::$items[$role->name]['items'] = array_diff(static::$items[$role->name]['items'], [$item->name]);
            unset(static::$roles[$role->name], static::$permissions[$role->name]);
        }
        return (bool)$result;
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
        $result = RolesItems::deleteAll(RolesItems::find()->byRole($role->name)->byItems($names)->where);
        static::$items[$role->name]['items'] = array_diff(static::$items[$role->name]['items'], $names);
        unset(static::$roles[$role->name], static::$permissions[$role->name]);
        return (bool)$result;
    }



    public function remove($itemName)
    {
        $result = Items::deleteAll(Items::find()->byItem($itemName)->where);
        $this->detachLoop($itemName);
        unset(static::$items[$itemName], static::$roles[$itemName], static::$permissions[$itemName]);
        return (bool)$result;
    }

    /**
     * @param array $itemNames
     * @return bool
     */
    public function removeMulti(array $itemNames)
    {
        $result = Items::deleteAll(Items::find()->byItems($itemNames)->where);
        foreach ($itemNames as $name) {
            $this->detachLoop($name);
            unset(static::$items[$name], static::$roles[$name], static::$permissions[$name]);
        }
        return (bool)$result;
    }

    public function removeAll()
    {
        $result = Items::deleteAll();
        static::$items = static::$roles = static::$permissions = null;
        return (bool)$result;
    }


    /**
     * @inheritdoc
     */
    public function getAssignments($userId)
    {
        if (isset(static::$assignments[$userId])) {
            return static::$assignments[$userId];
        }
        return static::$assignments[$userId] = array_keys(UsersItems::find()->byUserId($userId)->indexBy('item')->beginCache()->asArray()->all());
    }

    /**
     * @inheritdoc
     */
    public function assign($userId, array $roles)
    {
        $rows = [];
        foreach ($roles as $role) {
            if (!$role instanceof Role) {
                throw new RBACException(RBACException::UNKNOWN_TYPE, ['name' => serialize($role)]);
            }
            if ($this->hasAssigned($userId, $role->name)) {
                throw new RBACException("Duplicate role: {$role->name}");
            }
            $rows[] =[$userId, $role->name];
            static::$assignments[$userId][] = $role->name;
        }
        //unset(static::$assignments[$userId]);

        return (bool)static::$connection->createCommand()->batchInsert(UsersItems::tableName(), ['user_id', 'item'], $rows)->execute();
    }


    /**
     * @inheritdoc
     */
    public function revoke($userId, array $roles)
    {
        //unset(static::$assignments[$userId]);
        $names = [];
        foreach ($roles as $role) {
            if (!$role instanceof Role) {
                throw new RBACException(RBACException::UNKNOWN_TYPE, ['name' => serialize($role)]);
            }
            $names[] = $role->name;
        }

        if (!(static::$assignments[$userId] = array_diff(static::$assignments[$userId],$names))) {
            unset(static::$assignments[$userId]);
        }

        return (bool)UsersItems::deleteAll(UsersItems::find()->byUserId($userId)->byItems($names)->where);
    }

    /**
     * @inheritdoc
     */
    public function revokeAll($userId)
    {
        unset(static::$assignments[$userId]);
        return (bool)UsersItems::deleteAll(UsersItems::find()->byUserId($userId)->where);
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
            foreach ($names as $name => $value) {
                if (($role = $this->get($name)) && $role instanceof Role) {
                    if (!empty(static::$items[$role->name]['items'])) {
                        static::$items[$role->name]['items'] = array_diff(static::$items[$role->name]['items'], [$item->name]);
                        unset(static::$roles[$role->name], static::$permissions[$role->name]);
                    }
                }
            }
        }
    }
}