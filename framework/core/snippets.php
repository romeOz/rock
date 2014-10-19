<?php
return [

    'ListView' => [
        'class'        => \rock\snippets\ListView::className(),
    ],

    'Date' => [
        'class'        => \rock\snippets\Date::className(),
    ],

    'For' => [
        'class'        => \rock\snippets\ForSnippet::className(),
    ],

    'Formula' => [
        'class'        => \rock\snippets\Formula::className(),
    ],

    'If' => [
        'class'        => \rock\snippets\IfSnippet::className(),
    ],

    'Pagination' => [
        'class'        => \rock\snippets\Pagination::className(),
    ],

    'request\Get' => [
        'class'        => 'rock\snippets\request\Get',
    ],

    'request\Post' => [
        'class'        => 'rock\snippets\request\Post',
    ],

    'CSRF' => [
        'class'        => \rock\snippets\CSRF::className(),
    ],

    'Url' => [
        'class'        => \rock\snippets\Url::className(),
    ],

    'CaptchaView' => [
        'class'        => \rock\snippets\CaptchaView::className(),
    ],

    'Thumb' => [
        'class'        => \rock\snippets\Thumb::className(),
    ],


    'ActiveForm' => [
        'class' => \rock\snippets\html\ActiveForm::className(),
    ]

];