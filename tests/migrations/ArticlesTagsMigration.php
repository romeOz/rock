<?php

namespace rockunit\migrations;


use rock\db\Migration;
use rock\db\Schema;

class ArticlesTagsMigration extends Migration
{
    public $table = 'articles_tags';
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
                    'resource_id' => Schema::TYPE_INTEGER . ' unsigned NOT NULL',
                    'tag_id' => Schema::TYPE_INTEGER . ' unsigned NOT NULL',
                    'order_index' => Schema::TYPE_INTEGER . ' unsigned NOT NULL DEFAULT 0',
                ],
                $tableOptions,
                true
            );
            $this->addPrimaryKey('',$this->table,['resource_id', 'tag_id']);
            $this->addForeignKey("fk_{$this->table}_tags_id", $this->table,'tag_id', 'tags', 'id', 'CASCADE', 'CASCADE');

        } catch (\Exception $e) {
            return;
        }



        $this->batchInsert(
            $this->table,
            ['resource_id', 'tag_id'],
            [
                [1, 1],
                [2, 1],
                [2, 2],
                [2, 3],
                [1, 2],
            ]
        );
    }


    public function down()
    {
        $this->dropTable($this->table, true);
    }
} 