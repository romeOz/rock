<?php

namespace rockunit\migrations;


use rock\db\Migration;
use rock\db\Schema;

class ArticlesMigration extends Migration
{
    public $table = 'articles';
    public function up()
    {
        if ((bool)$this->connection->createCommand("SHOW TABLES LIKE '{$this->table}'")->execute()) {
            return;
        }

        $tableOptions = null;
        if ($this->connection->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        $this->createTable(
            $this->table,
            [
                'id' => Schema::TYPE_PK,
                'title'=> Schema::TYPE_STRING . '(255) NOT NULL',
                'category' => Schema::TYPE_INTEGER . ' unsigned',
                'createdon' => Schema::TYPE_TIMESTAMP,
                'editedon' => Schema::TYPE_TIMESTAMP,

            ],
            $tableOptions,
            true
        );

        $this->createIndex("idx_{$this->table}_category", $this->table, 'category');

        $this->batchInsert(
            $this->table,
            ['title', 'category'],
            [
                ['foo', 3],
                ['bar', 4],
                ['test_1', null],
                ['test_2', null],
                ['test_3', null],
            ]
        );
    }


    public function down()
    {
        $this->dropTable($this->table);
    }
} 