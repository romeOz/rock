<?php

/**
 * Container "Models"
 */
use rock\i18n\i18n;
use rock\Rock;
use rock\route\Route;
use rock\template\Template;

return [
    'route' => [
        'class' => \rock\route\Route::className(),
        'singleton' => true,
        'rules' =>
            [
                [
                    [Route::GET],
                    '/' ,
                    function(array $data){
                        return (new \apps\frontend\controllers\MainController())->method('actionIndex', $data);
                    }
                ],

                [
                    \rock\route\Route::GET,
                    '*',
                    function(array $data) {
                        return (new \apps\frontend\controllers\MainController())->notPage('index');
                    }
                ],
            ],
        'fail' => function(\rock\route\Route $route) {
                return (new \apps\frontend\controllers\MainController())->notPage('index');
        }
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