<?php

namespace apps\common\migrations;


use apps\common\rbac\UserRole;
use rock\db\Migration;
use rock\db\Schema;
use rock\rbac\Permission;
use rock\rbac\RBACInterface;
use rock\rbac\Role;

class AccessItemsMigration extends Migration
{
    public $table = 'access_items';
    public function up()
    {
        if ((bool)$this->connection->createCommand("SHOW TABLES LIKE '{$this->table}'")->execute()) {
            return;
        }
        $tableOptions = null;
        if ($this->connection->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }
        try {
            $this->createTable(
                $this->table,
                [
                    'name' => Schema::TYPE_STRING . '(64) NOT NULL',
                    'type' => Schema::TYPE_BOOLEAN . '(2) unsigned NOT NULL DEFAULT 1',
                    'description' => Schema::TYPE_STRING . '(255) NOT NULL DEFAULT \'\'',
                    'data' => Schema::TYPE_TEXT . ' NOT NULL',
                    'menuindex' => Schema::TYPE_INTEGER . ' unsigned NOT NULL DEFAULT 0',
                ],
                $tableOptions,
                true
            );
            $this->addPrimaryKey('', $this->table, 'name');
            $this->createIndex("idx_{$this->table}_type", $this->table, 'type');
        } catch (\Exception $e) {
            return;
        }



        $this->batchInsert(
            $this->table,
            ['name', 'type', 'description', 'data', 'menuindex'],
            [
                ['godmode', RBACInterface::TYPE_ROLE, 'super admin', serialize(new Role), 999],
                ['admin', RBACInterface::TYPE_ROLE, 'administrator',serialize(new Role), 998],
                ['editor', RBACInterface::TYPE_ROLE, 'editor', serialize(new Role), 997],
                ['moderator', RBACInterface::TYPE_ROLE, 'moderator', serialize(new Role), 996],
                ['user', RBACInterface::TYPE_ROLE, 'user', serialize(new UserRole), 995],
                ['guest', RBACInterface::TYPE_ROLE, 'guest', serialize(new Role), 994],
                ['read_post', RBACInterface::TYPE_PERMISSION, 'read post', serialize(new Permission), 0],
                ['create_post', RBACInterface::TYPE_PERMISSION, 'create post', serialize(new Permission), 0],
                ['update_post', RBACInterface::TYPE_PERMISSION, 'update post', serialize(new Permission), 0],
                ['delete_post', RBACInterface::TYPE_PERMISSION, 'delete post', serialize(new Permission), 0],
            ]
        );
    }


    public function down()
    {
        $this->dropTable($this->table, true);
    }
} 