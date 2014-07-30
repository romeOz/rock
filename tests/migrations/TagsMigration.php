<?php

namespace rockunit\migrations;


use rock\db\Migration;
use rock\db\Schema;

class TagsMigration extends Migration
{
    public $table = 'tags';
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

            ],
            $tableOptions,
            true
        );



        $this->batchInsert(
            $this->table,
            ['title'],
            [
                ['tag_1'],
                ['tag_2'],
                ['tag_3'],
                ['tag_4'],
                ['tag_5'],
            ]
        );
    }


    public function down()
    {
        $this->dropTable($this->table);
    }
} 