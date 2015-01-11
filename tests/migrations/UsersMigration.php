<?php

namespace rockunit\migrations;

use rock\db\Migration;
use rock\db\Schema;
use rock\helpers\NumericHelper;
use rock\helpers\StringHelper;
use rock\Rock;

class UsersMigration extends Migration
{
    public static $table = 'users';
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

        $this->createTable(
            $table,
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

        $this->createIndex("idx_{$table}_username_hash", $table, 'username_hash', true);
        $this->createIndex("idx_{$table}_email_hash", $table, 'email_hash', true);
        $this->createIndex("idx_{$table}_status", $table, 'status');

        $security = Rock::$app->security;
        $this->batchInsert(
            $table,
            ['username', 'username_hash', 'password', 'token', 'email', 'email_hash', 'status', 'url'],
            [
                ['Tom', $this->hash('Tom'), $security->generatePasswordHash('123456'), $security->generateRandomKey() . '_' . time(), 'tom@gmail.com', $this->hash('tom@gmail.com'), 2, ''],
                ['Jane', $this->hash('Jane'), $security->generatePasswordHash('123456'), $security->generateRandomKey() . '_' . time(), 'jane@hotmail.com', $this->hash('jane@hotmail.com'), 2, ''],
                ['Linda',$this->hash('Linda'), $security->generatePasswordHash('123456'), $security->generateRandomKey() . '_' . time(), 'linda@gmail.com', $this->hash('linda@gmail.com'), 3, '/linda/'],
            ]
        );
    }


    public function down()
    {
//        $table = AccessUsersItemsMigration::$table;
//        $this->dropForeignKey("fk_{$table}_user", $table);
        $table = static::$table;
        $this->connection->createCommand("SET FOREIGN_KEY_CHECKS=0")->execute();
        $this->dropTable($table, true);
        $this->connection->createCommand("SET FOREIGN_KEY_CHECKS=1")->execute();
    }

    protected function hash($value)
    {
        return NumericHelper::hexToBin(md5($value. static::$table));
    }

    protected function prepareUrl($username)
    {
        return mb_strtolower('/profile/' . $this->translitUsername($username) . '/', 'UTF-8');
    }

    /**
     * Translit username
     *
     * @param string $username
     * @return string
     */
    protected function translitUsername($username)
    {
        return strtolower(
            StringHelper::translit(
                preg_replace(
                    [
                        '/[^\\w\\s\-]+/iu',
                        '/\\s+/iu',
                        '/[\_\-]+/iu'
                    ],
                    ['', '-', '-'],
                    $username
                )
            )
        );
    }
} 