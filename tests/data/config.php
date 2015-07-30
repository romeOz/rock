<?php
use Monolog\Handler\NullHandler;
use rock\execute\CacheExecute;
use rock\log\Log;
use rock\rbac\PhpManager;
use rockunit\mocks\SessionMock;
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
                ['class' => AccessItemsMigration::className()],
                ['class' => AccessRolesItemsMigration::className()],
                ['class' => AccessAssignmentsMigration::className()],
                ['class' => \rockunit\migrations\UsersMigration::className()]
            ]
        ],
    ],
    'classes' => [
        'log' => [
            'class' => Log::className(),
            'handlers' => [new NullHandler()]
        ],
        'request' => [
            'homeUrl' => 'http://site.com/'
        ],
//        'cache' => [
//            'class' => \rock\cache\CacheStub::className()
//        ],
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
