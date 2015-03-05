<?php
use rock\rbac\Permission;
use rock\rbac\Role;
use rock\rbac\RBACInterface;
use rock\rbac\UserRole;

return [

    // HERE ARE YOUR MANAGEMENT PERMISSIONS
    'create_post' => [
        'type' => RBACInterface::TYPE_PERMISSION,
        'description' => 'create post',
        'data' => Permission::className(),
    ],
    'update_post' => [
        'type' => RBACInterface::TYPE_PERMISSION,
        'description' => 'update post',
        'data' => Permission::className(),
    ],
    'delete_post' => [
        'type' => RBACInterface::TYPE_PERMISSION,
        'description' => 'delete post',
        'data' => Permission::className(),
    ],
    'read_post' => [
        'type' => RBACInterface::TYPE_PERMISSION,
        'description' => 'read post',
        'data' => Permission::className(),
    ],

    'godmode' => [
        'type' => RBACInterface::TYPE_ROLE,
        'description' => 'super admin',
        'items' => [
            'admin',        // can do all that admin can
        ],
        'data' => Role::className()
    ],

    'admin' => [
        'type' => RBACInterface::TYPE_ROLE,
        'description' => 'administrator',
        'items' => [
            'editor',
            'delete_post'
        ],
        'data' => Role::className()
    ],

    'editor' => [
        'type' => RBACInterface::TYPE_ROLE,
        'description' => 'editor',
        'items' => [
            'user',
            'create_post',
            'update_post',
        ],
        'data' => Role::className()
    ],

    'moderator' => [
        'type' => RBACInterface::TYPE_ROLE,
        'description' => 'moderator',
        'items' => [
            'user'
        ],
        'data' => Role::className()
    ],

    'user' => [
        'type' => RBACInterface::TYPE_ROLE,
        'description' => 'user',
        'items' => [
            'guest',
        ],
        'data' => UserRole::className(),
    ],

    // AND THE ROLES
    'guest' => [
        'type' => RBACInterface::TYPE_ROLE,
        'description' => 'guest',
        'items' => [
            'read_post',
        ],
        'data' => Role::className()
    ],
];