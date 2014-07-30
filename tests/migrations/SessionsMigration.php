<?php

namespace rockunit\migrations;


use rock\db\Migration;
use rock\db\Schema;

class SessionsMigration extends Migration
{
    public $table = 'sessions';
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
                'id' => Schema::TYPE_CHAR . '(40) NOT NULL',
                'expire' => Schema::TYPE_INTEGER,
                'data' => Schema::TYPE_BLOB,
            ],
            $tableOptions,
            true
        );
        $this->addPrimaryKey('',$this->table, 'id');
    }


    public function down()
    {
        $this->dropTable($this->table);
    }
} 