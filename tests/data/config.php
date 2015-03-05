<?php
use rock\execute\CacheExecute;
use rock\log\Log;
use rock\rbac\PhpManager;
use rockunit\core\session\mocks\SessionMock;
use rockunit\mocks\CookieMock;
use rockunit\migrations\AccessItemsMigration;
use rockunit\migrations\AccessRolesItemsMigration;
use rockunit\migrations\AccessAssignmentsMigration;

return [
    'databases' => [
        'mysql' => [
            'dsn' => 'mysql:host=127.0.0.1;dbname=rocktest',
            'username' => 'travis',
            'password' => '',
            'fixture' => __DIR__ . '/mysql.sql',
            'migrations' => [
                AccessItemsMigration::className(),
                AccessRolesItemsMigration::className(),
                AccessAssignmentsMigration::className(),
            ]
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
            'path' => '@rockunit/runtime/cache/_execute'
        ],
        'rbac' => [
            'class' => PhpManager::className(),
            'path' => '@rockunit/data/rbac/roles.php',
            'pathAssignments' => '@rockunit/data/rbac/assignments.php'
        ]
    ]
];
