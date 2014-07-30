<?php
// Widgets
return [    
    'activeForm' => [
        'class' => \rock\widgets\ActiveForm::className(),
        'singleton' => true
    ],
    'activeField' => [
        'class' => \rock\widgets\ActiveField::className(),
        'singleton' => true
    ],
    'widget\captcha' => [
        'class' => \rock\widgets\Captcha::className(),
        'singleton' => true
    ],
];