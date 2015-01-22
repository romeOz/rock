<?php

namespace rockunit\migrations;


use rock\db\Migration;
use rock\db\Schema;

class NewsMigration extends Migration
{
    public $table = 'news';
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
                'ctime' => Schema::TYPE_TIMESTAMP,
                'mtime' => Schema::TYPE_TIMESTAMP,

            ],
            $tableOptions,
            true
        );


        $this->createIndex("idx_{$this->table}_category", $this->table, 'category');
        $this->batchInsert(
            $this->table,

            ['title', 'category'],
            [
                ['news_1', 3],
                ['news_2', 4],
                ['news_3', null],
                ['news_4', null],
                ['news_5', null],
                ['About cats', null],
                ['About dogs', null],
            ]
        );
    }

    public function down()
    {
        $this->dropTable($this->table);
    }
} 