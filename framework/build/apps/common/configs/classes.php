<?php

use rock\route\Route;
use rock\template\Template;

return [
    'route' => [
        'class' => \rock\route\Route::className(),
        'rules' =>
            [
                [
                    [Route::GET],
                    '/' ,
                    [new \apps\frontend\controllers\MainController(), 'actionIndex']
                ],
                [
                    \rock\route\Route::GET,
                    '*',
                    [new \apps\frontend\controllers\MainController(), 'notPage']
                ],
            ],
    ],

    'template' => [
        'cssFiles' => [
            Template::POS_HEAD => [
                '<link href="/assets/css/bootstrap.min.css" rel="stylesheet"/>',
                '<link href="/assets/css/demo.css" rel="stylesheet"/>'
            ],
        ],
        'jsFiles' => [
            Template::POS_HEAD => [
                '<!--[if lt IE 9]><script src="/assets/js/html5shiv.min.js"></script><![endif]-->',
                '<script src="/assets/js/jquery.min.js"></script>',
                '<script src="/assets/js/bootstrap.min.js"></script>'
            ]
        ]
    ]
];