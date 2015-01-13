<?php

namespace rockunit\core\helpers\mocks;


use rock\csrf\CSRF;
use rock\helpers\Html;
use rock\Rock;

class HtmlMock extends Html
{
    public static function beginForm($action = null, $name = null, $method = 'post', $options = [])
    {
        Rock::$app->di['csrf'] = [
            'class' => CSRF::className(),
            'enableCsrfValidation' => false
        ];

        $form = parent::beginForm($action, $method, $name, $options);
        Rock::$app->di['csrf'] = [
            'class' => CSRF::className(),
            'enableCsrfValidation' => true
        ];
        return $form;
    }
}