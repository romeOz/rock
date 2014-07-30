<?php

namespace rockunit\migrations;


use rock\db\Migration;
use rock\db\Schema;

class AccessUsersItemsMigration extends Migration
{
    public static $table = 'access_users_items';
    public function up()
    {
        $table = static::$table;
        //if ((bool)$this->db->createCommand("SHOW TABLES LIKE '{$table}'")->execute()) {
            //$this->down();
        //}

        $tableOptions = null;
        if ($this->connection->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        //try {
            $this->createTable(
                $table,
                [
                    'user_id' => Schema::TYPE_INTEGER . ' unsigned NOT NULL',
                    'item' => Schema::TYPE_STRING . '(64) NOT NULL',
                ],
                $tableOptions,
                true
            );
            $this->addPrimaryKey('',$table,['user_id', 'item']);
            $this->addForeignKey("fk_{$table}_user", $table,'user_id', 'users', 'id', 'CASCADE', 'CASCADE');
            $this->addForeignKey("fk_{$table}_item", $table,'item', 'access_items', 'name', 'CASCADE', 'CASCADE');

//        } catch (\Exception $e) {
//            return;
//        }



        $this->batchInsert(
            $table,
            ['user_id', 'item'],
            [
                [1, 'godmode'],
                [2, 'editor'],
            ]
        );
    }


    public function down()
    {
        $table = static::$table;
        $this->connection->createCommand("SET FOREIGN_KEY_CHECKS=0")->execute();
        $this->dropTable($table, true);
        $this->connection->createCommand("SET FOREIGN_KEY_CHECKS=1")->execute();
    }

} 