<?php
return array (
  'create_post' => 
  array (
    'type' => 2,
    'description' => 'create post',
    'data' => 'rock\\rbac\\Permission',
  ),
  'update_post' => 
  array (
    'type' => 2,
    'description' => 'update post',
    'data' => 'rock\\rbac\\Permission',
  ),
  'delete_post' => 
  array (
    'type' => 2,
    'description' => 'delete post',
    'data' => 'rock\\rbac\\Permission',
  ),
  'read_post' => 
  array (
    'type' => 2,
    'description' => 'read post',
    'data' => 'rock\\rbac\\Permission',
  ),
  'godmode' => 
  array (
    'type' => 1,
    'description' => 'super admin',
    'items' => 
    array (
      0 => 'admin',
    ),
    'data' => 'rock\\rbac\\Role',
  ),
  'admin' => 
  array (
    'type' => 1,
    'description' => 'administrator',
    'items' => 
    array (
      0 => 'editor',
      1 => 'delete_post',
    ),
    'data' => 'rock\\rbac\\Role',
  ),
  'editor' => 
  array (
    'type' => 1,
    'description' => 'editor',
    'items' => 
    array (
      0 => 'user',
      1 => 'create_post',
      2 => 'update_post',
    ),
    'data' => 'rock\\rbac\\Role',
  ),
  'moderator' => 
  array (
    'type' => 1,
    'description' => 'moderator',
    'items' => 
    array (
      0 => 'user',
    ),
    'data' => 'rock\\rbac\\Role',
  ),
  'user' => 
  array (
    'type' => 1,
    'description' => 'user',
    'items' => 
    array (
      0 => 'guest',
    ),
    'data' => 'apps\\common\\rbac\\UserRole',
  ),
  'guest' => 
  array (
    'type' => 1,
    'description' => 'guest',
    'items' => 
    array (
      0 => 'read_post',
    ),
    'data' => 'rock\\rbac\\Role',
  ),
);
