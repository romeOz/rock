<?php
return [

    'ListView' => [
        'class'        => \rock\snippets\ListView::className(),
        'singleton'       => true
    ],

    'Date' => [
        'class'        => \rock\snippets\Date::className(),
        'singleton'       => true
    ],

    'For' => [
        'class'        => \rock\snippets\ForSnippet::className(),
        'singleton'       => true
    ],

    'Formula' => [
        'class'        => \rock\snippets\Formula::className(),
        'singleton'       => true
    ],

    'If' => [
        'class'        => \rock\snippets\IfSnippet::className(),
        'singleton'       => true
    ],

    'Pagination' => [
        'class'        => \rock\snippets\Pagination::className(),
        'singleton'       => true,
    ],

    'request\Get' => [
        'class'        => 'rock\snippets\request\Get',
        'singleton'       => true
    ],

    'request\Post' => [
        'class'        => 'rock\snippets\request\Post',
        'singleton'       => true
    ],

    'Token' => [
        'class'        => \rock\snippets\Token::className(),
        'singleton'       => true
    ],

    'Url' => [
        'class'        => \rock\snippets\Url::className(),
        'singleton'       => true
    ],

    'CaptchaView' => [
        'class'        => \rock\snippets\CaptchaView::className(),
        'singleton'       => true
    ],

    'Thumb' => [
        'class'        => \rock\snippets\Thumb::className(),
        'singleton'       => true
    ],


    'ActiveForm' => [
        'class' => \rock\snippets\html\ActiveForm::className(),
        'singleton' => true,
    ]

];