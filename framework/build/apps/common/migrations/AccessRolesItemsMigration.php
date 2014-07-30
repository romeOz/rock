<?php

namespace apps\common\migrations;


use rock\db\Migration;
use rock\db\Schema;
use rock\Rock;

class AccessRolesItemsMigration extends Migration
{
    public $table = 'access_roles_items';
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
                    'role' => Schema::TYPE_STRING . '(64) NOT NULL',
                    'item' => Schema::TYPE_STRING . '(64) NOT NULL',
                ],
                $tableOptions,
                true
            );
            $this->addPrimaryKey('',$this->table,['role', 'item']);
            $this->addForeignKey("fk_{$this->table}_role", $this->table,'role', 'access_items', 'name', 'CASCADE', 'CASCADE');
            $this->addForeignKey("fk_{$this->table}_item", $this->table,'item', 'access_items', 'name', 'CASCADE', 'CASCADE');

        } catch (\Exception $e) {
            return;
        }



        $this->batchInsert(
            $this->table,
            ['role', 'item'],
            [
                ['godmode', 'admin'],
                ['admin', 'editor'],
                ['admin', 'delete_post'],
                ['editor', 'user'],
                ['editor', 'create_post'],
                ['editor', 'update_post'],
                ['moderator','user'],
                ['user','guest'],
                ['guest','read_post'],
            ]
        );
    }


    public function down()
    {
        $this->dropTable($this->table, true);
    }
} 