<?php

namespace rock\rbac;

use rock\db\Connection;
use rock\db\Query;
use rock\db\SelectBuilder;
use rock\helpers\ArrayHelper;
use rock\helpers\Instance;

class DBManager extends RBAC
{
    /**
     * @var Connection|string|array the DB connection object or the application component ID of the DB connection.
     * After the DbManager object is created, if you want to change this property, you should only assign it
     * with a DB connection object.
     */
    public $connection = 'db';
    /**
     * @var string the name of the table storing authorization items. Defaults to "access_items".
     */
    public $itemsTable = '{{%access_items}}';
    /**
     * @var string the name of the table storing roles. Defaults to "access_roles".
     */
    public $rolesTable = '{{%access_items}} {{%roles}}';
    /**
     * @var string the name of the table storing authorization item hierarchy. Defaults to "access_roles_items".
     */
    public $rolesItemsTable = '{{%access_roles_items}}';
    /**
     * @var string the name of the table storing authorization item assignments. Defaults to "access_assignments".
     */
    public $assignmentTable = '{{%access_assignments}}';
    /**
     * List roles and permissions.
     * @var array
     */
    protected static $items = [];
    /**
     * List assignments.
     * @var array
     */
    protected static $assignments = [];

    public function init()
    {
        $this->connection = Instance::ensure($this->connection);

        if (!empty(static::$items)) {
            return;
        }
        $this->load();
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
        if (!$this->connection->createCommand()
            ->insert($this->itemsTable, [
                'name' => $item->name,
                'type' => $item->type,
                'description' => isset($item->description) ? $item->description : '',
                'data' => $item->data === null ? '' : serialize($item->data),
            ])->execute()) {
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

        $result = $this->connection->createCommand()
            ->insert($this->rolesItemsTable, ['role' => $role->name, 'item' => $item->name])
            ->execute();

        static::$items[$role->name]['items'] = isset(static::$items[$role->name]['items']) ? static::$items[$role->name]['items'] : [];
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
        $result = $this->connection
            ->createCommand()
            ->batchInsert($this->rolesItemsTable, ['role', 'item'], $rows)
            ->execute();
        $items = isset(static::$items[$role->name]['items']) ? static::$items[$role->name]['items'] : [];
        static::$items[$role->name]['items'] =
            array_merge($items, $itemNames);
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
            $result = $this->connection
                ->createCommand()
                ->delete($this->rolesItemsTable, ['and', '[[role]]=:role', '[[item]]=:item'], [':role' => $role->name, ':item' => $item->name])
                ->execute();
            static::$items[$role->name]['items'] = array_diff(static::$items[$role->name]['items'], [$item->name]);
            unset(static::$roles[$role->name], static::$permissions[$role->name]);
        }
        return (bool)$result;
    }

    /**
     * Detach an items
     *
     * @param Role $role
     * @param Item[] $items
     * @return bool
     */
    public function detachItems(Role $role, array $items)
    {
        $names = [];
        foreach ($items as $item) {
            $names[] = $item->name;
        }
        $result = $this->connection
            ->createCommand()
            ->delete($this->rolesItemsTable, ['role' => $role->name, 'item' => $names])
            ->execute();

        static::$items[$role->name]['items'] = array_diff(static::$items[$role->name]['items'], $names);
        unset(static::$roles[$role->name], static::$permissions[$role->name]);
        return (bool)$result;
    }

    public function remove($itemName)
    {
        $result = $this->connection
            ->createCommand()
            ->delete($this->itemsTable, '[[name]]=:item', [':item' => $itemName])
            ->execute();

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
        $result = $this->connection
            ->createCommand()
            ->delete($this->itemsTable, ['name' => $itemNames])
            ->execute();
        foreach ($itemNames as $name) {
            $this->detachLoop($name);
            unset(static::$items[$name], static::$roles[$name], static::$permissions[$name]);
        }
        return (bool)$result;
    }

    public function removeAll()
    {
        $result = $this->connection
            ->createCommand()
            ->delete($this->itemsTable)
            ->execute();

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
        //@TODO cache
        $assignments = (new Query)
            ->from($this->assignmentTable)
            ->where(['user_id' => $userId])
            ->indexBy('item')
            ->all($this->connection);
        return static::$assignments[$userId] = array_keys($assignments);
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

        return (bool)$this->connection->createCommand()->batchInsert($this->assignmentTable, ['user_id', 'item'], $rows)->execute();
    }

    /**
     * @inheritdoc
     */
    public function revoke($userId, array $roles)
    {
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

        return (bool)$this->connection
            ->createCommand()
            ->delete($this->assignmentTable, ['user_id' => $userId, 'item' => $names])
            ->execute();
    }

    /**
     * @inheritdoc
     */
    public function revokeAll($userId)
    {
        unset(static::$assignments[$userId]);
        return (bool)$this->connection
            ->createCommand()
            ->delete($this->assignmentTable, ['user_id' => $userId])
            ->execute();
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

    protected function load()
    {
        //@TODO cache
        if (!$dataItems = (new Query)
            ->from($this->itemsTable)
            ->orderBy(['order_index' => SORT_DESC])
            ->indexBy('name')
            ->all($this->connection)) {

            throw new RBACException('Items is empty.');
        }
        static::$items = $dataItems;

        $alias = Query::alias($this->rolesTable, $this->rolesTable);
        //@TODO cache
        if (!$dataRolesItems = (new Query)
            ->select(
                SelectBuilder::selects(
                    [
                        ['roles' => ['name', 'type', 'description', 'data']],
                        ['access_items' => ['name', 'type', 'description', 'data'], 'items']
                    ])
            )
            ->from($this->rolesItemsTable)
            ->innerJoin($this->itemsTable, "{$this->rolesItemsTable}.item = {$this->itemsTable}.name")
            ->innerJoin($this->rolesTable, "{$this->rolesItemsTable}.role = {$alias}.name")
            ->andWhere(["{$alias}.[[type]]" => RBACInterface::TYPE_ROLE])
            ->orderBy(["{$alias}.[[order_index]]" => SORT_DESC])
            ->asSubattributes()
            ->all($this->connection)) {

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
}