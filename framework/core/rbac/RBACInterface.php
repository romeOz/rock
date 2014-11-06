<?php

namespace rock\rbac;


use rock\base\Arrayable;

interface RBACInterface extends \IteratorAggregate, \Countable, ItemInterface
{


    /**
     * @param string $itemName
     * @return Item
     */
    public function get($itemName);

    /**
     * @param array $names
     * @return Item[]
     */
    public function getMulti(array $names);

    /**
     * @param array $only  list of items whose value needs to be returned.
     * @param array $exclude list of items whose value should NOT be returned.
     * @return array
     */
    public function getAll(array $only = [], array $exclude = []);

    /**
     * Get Role
     *
*@param string $name - name of role
     * @return Role|null
     * @throws RBACException
     */
    public function getRole($name);
    /**
     * Get permission
     *
*@param string    $name - name of permission
     * @return Permission|null
     * @throws RBACException
     */
    public function getPermission($name);

    /**
     * Get count of items
     * @return int
     */
    public function getCount();

    /**
     * Creates a new Role object.
     * Note that the newly created role is not added to the RBAC system yet.
     * You must fill in the needed data and call [[add()]] to add it to the system.
     * @param string $name the role name
     * @return Role the new Role object
     */
    public function createRole($name);

    /**
     * Creates a new Permission object.
     * Note that the newly created permission is not added to the RBAC system yet.
     * You must fill in the needed data and call [[add()]] to add it to the system.
     * @param string $name the permission name
     * @return Permission the new Permission object
     */
    public function createPermission($name);

    /**
     * Adds a role, permission or rule to the RBAC system.
     *
     * @param Role|Permission|Item $item
     * @return boolean whether the role, permission or rule is successfully added to the system
     * @throws RBACException if data validation or saving fails (such as the name of the role or permission is not unique)
     */
    public function add(Item $item);

    /**
     * Adds an item as a child of another item.
     *
     * @param Role $role
     * @param Item                         $item
     * @return bool
     */
    public function attachItem(Role $role, Item $item);

    /**
     * Adds an items as a child of another item.
     *
     * @param Role  $role
     * @param Item[] $items
     * @return bool
     */
    public function attachItems(Role $role, array $items);

    /**
     * Detach an item
     *
     * @param Role $role
     * @param Item                         $item
     * @return bool
     */
    public function detachItem(Role $role, Item $item);

    /**
     * Detach an items
     *
     * @param Role $role
     * @param Item[]                         $items
     * @return bool
     */
    public function detachItems(Role $role, array $items);

    /**
     * @param string $name
     * @return bool
     */
    public function has($name);

    /**
     * @param Role $role
     * @param  string    $itemName
     * @return bool
     */
    public function hasChild(Role $role, $itemName);

    /**
     * @param Role  $role
     * @param string[] $itemNames
     * @return bool
     */
    public function hasChildren(Role $role, array $itemNames);

    /**
     * Remove item
     *
     * @param string $itemName
     * @return bool
     */
    public function remove($itemName);

    /**
     * @param array $itemNames
     * @return bool
     */
    public function removeMulti(array $itemNames);

    /**
     * @return bool
     */
    public function removeAll();




    /**
     * @param int      $userId
     * @param string $itemName
     * @param array    $params
     * @return bool
     */
    public function check($userId, $itemName, array $params = null);


    /**
     * Assigns a role to a user.
     *
     * @param string|integer $userId the user ID (see [[User::id]])
     * @param Role[] $roles the rule to be associated with this assignment. If not null, the rule
     * will be executed when [[allow()]] is called to check the user permission.     
     * @return bool
     * @throws RBACException if the role has already been assigned to the user
     */
    public function assign($userId, array $roles);

    /**
     * Returns the item assignments for the specified user.
     * @param int $userId the user ID (see [[User::id]])
     * @return array the item assignment information for the user. An empty array will be
     * returned if there is no item assigned to the user.
     */
    public function getAssignments($userId);




    /**
     * Returns a value indicating whether the item has been assigned to the user.
     *
     * @param int $userId the user ID (see [[User::id]])
     * @param string $roleName the role name
     * @return boolean whether the item has been assigned to the user.
     */
    public function hasAssigned($userId, $roleName);
    /**
     * Revokes a role from a user.
     *
     * @param string|integer $userId the user ID (see [[User::id]]).
     * @param Role[] $roles the rule to be associated with this assignment. If not null, the rule
     * @return boolean whether the revoking is successful
     */
    public function revoke($userId, array $roles);

    /**
     * Revokes all roles from a user.
     * @param mixed $userId the user ID (see [[User::id]])
     * @return boolean whether the revoking is successful
     */
    public function revokeAll($userId);
}