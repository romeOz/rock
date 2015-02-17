<?php
use rock\execute\CacheExecute;
use rock\log\Log;
use rock\rbac\PhpManager;
use rockunit\core\session\mocks\SessionMock;
use rockunit\mocks\CookieMock;
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
    ],
    'classes' => [
        'log' => [
            'class' => Log::className(),
            'path' => __DIR__ . '/runtime/logs'
        ],
        'cache' => [
            'class' => \rock\cache\CacheStub::className()
        ],
        'session' => [
            'class' => SessionMock::className(),
        ],
        'cookie' => [
            'class' => CookieMock::className(),
        ],
        'execute' => [
            'class' => CacheExecute::className(),
            'path' => '@tests/runtime/cache/_execute'
        ],
        'rbac' => [
            'class' => PhpManager::className(),
            'path' => '@tests/core/rbac/src/rbac.php'
        ]
    ]
];
