<?php
use rockunit\migrations\AccessItemsMigration;
use rockunit\migrations\AccessRolesItemsMigration;
use rockunit\migrations\AccessUsersItemsMigration;
use rockunit\migrations\SessionsMigration;
use rockunit\migrations\UsersMigration;

return [
    'databases' => [
        'cubrid' => [
            'dsn' => 'cubrid:dbname=demodb;host=localhost;port=33000',
            'username' => 'dba',
            'password' => '',
            'fixture' => __DIR__ . '/cubrid.sql',
        ],
        'mysql' => [
            'dsn' => 'mysql:host=127.0.0.1;dbname=rocktest',
            'username' => 'travis',
            'password' => '',
            'fixture' => __DIR__ . '/mysql.sql',
            'migrations' => [
                UsersMigration::className(),
                AccessItemsMigration::className(),
                AccessRolesItemsMigration::className(),
                AccessUsersItemsMigration::className(),
                SessionsMigration::className(),
            ]
        ],
        'sqlite' => [
            'dsn' => 'sqlite::memory:',
            'fixture' => __DIR__ . '/sqlite.sql',
        ],
        'sqlsrv' => [
            'dsn' => 'sqlsrv:Server=localhost;Database=rocktest',
            'username' => '',
            'password' => '',
            'fixture' => __DIR__ . '/mssql.sql',
        ],
        'pgsql' => [
            'dsn' => 'pgsql:host=localhost;dbname=rocktest;port=5432;',
            'username' => 'postgres',
            'password' => 'postgres',
            'fixture' => __DIR__ . '/postgres.sql',
        ],
        'elasticsearch' => [
            'dsn' => 'elasticsearch://localhost:9200'
        ],
        'redis' => [
            'hostname' => 'localhost',
            'port' => 6379,
            'database' => 0,
            'password' => null,
        ],
    ],
    'sphinx' => [
        'sphinx' => [
            'dsn' => 'mysql:host=127.0.0.1;port=9306;',
            'username' => 'travis',
            'password' => '',
        ],
        'db' => [
            'dsn' => 'mysql:host=127.0.0.1;dbname=rocktest',
            'username' => 'travis',
            'password' => '',
            'fixture' => __DIR__ . '/sphinx/source.sql',
        ],
    ],
    'mongodb' => [
        'dsn' => 'mongodb://travis:test@localhost:27017',
        'defaultDatabaseName' => 'rocktest',
        'options' => [],
    ]
];
