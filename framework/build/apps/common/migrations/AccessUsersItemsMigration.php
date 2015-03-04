<?php

namespace apps\common\migrations;


use rock\db\Migration;
use rock\db\Schema;

class AccessUsersItemsMigration extends Migration
{
    public $table = 'access_assignments';
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
                    'user_id' => Schema::TYPE_INTEGER . ' unsigned NOT NULL',
                    'item' => Schema::TYPE_STRING . '(64) NOT NULL',
                ],
                $tableOptions,
                true
            );
            $this->addPrimaryKey('',$this->table,['user_id', 'item']);
            $this->addForeignKey("fk_{$this->table}_user", $this->table,'user_id', 'users', 'id', 'CASCADE', 'CASCADE');
            $this->addForeignKey("fk_{$this->table}_item", $this->table,'item', 'access_items', 'name', 'CASCADE', 'CASCADE');

        } catch (\Exception $e) {
            return;
        }

        $this->batchInsert(
            $this->table,
            ['user_id', 'item'],
            [
                [1, 'godmode'],
            ]
        );
    }


    public function down()
    {
        $this->dropTable($this->table, true);
    }

} 