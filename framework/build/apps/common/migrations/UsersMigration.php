<?php

namespace apps\common\migrations;

use rock\db\Migration;
use rock\db\Schema;
use rock\helpers\NumericHelper;
use rock\security\Security;

class UsersMigration extends Migration
{
    public $table = 'users';

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
                'username' => Schema::TYPE_STRING . '(100) NOT NULL',
                'username_hash' => Schema::TYPE_BINARY . '(16) NOT NULL',
                'password' => Schema::TYPE_CHAR . '(255) NOT NULL',
                'token' => Schema::TYPE_STRING ,
                'email' => Schema::TYPE_STRING . '(100) NOT NULL',
                'email_hash' => Schema::TYPE_BINARY . '(16) NOT NULL',
                'status' => Schema::TYPE_BOOLEAN . '(3) unsigned NOT NULL DEFAULT 2',
                'ctime' => Schema::TYPE_TIMESTAMP . ' NOT NULL DEFAULT CURRENT_TIMESTAMP()',
                'login_last' => Schema::TYPE_TIMESTAMP . ' NULL',
                'url' => Schema::TYPE_TEXT,
            ],
            $tableOptions,
            true
        );

        $this->createIndex("idx_{$this->table}_username_hash", $this->table, 'username_hash', true);
        $this->createIndex("idx_{$this->table}_email_hash", $this->table, 'email_hash', true);
        $this->createIndex("idx_{$this->table}_status", $this->table, 'status');

        $security = new Security();
        $this->batchInsert(
            $this->table,
            ['username', 'username_hash', 'password', 'token', 'email', 'email_hash', 'status', 'url'],
            [
                ['rock', $this->hash('rock'), $security->generatePasswordHash('rock'), $security->generateRandomKey() . '_' . time(), 'rock@site.com', $this->hash('rock@site.com'), 3, '/rock/'],
            ]
        );
    }


    protected function hash($value)
    {
        return NumericHelper::hexToBin(md5($value. $this->table));
    }
    
    public function down()
    {
        $this->dropTable($this->table);
    }
} 