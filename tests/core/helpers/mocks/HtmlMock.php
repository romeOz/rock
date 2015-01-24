<?php

namespace rockunit\core\helpers\mocks;


use rock\csrf\CSRF;
use rock\di\Container;
use rock\helpers\Html;

class HtmlMock extends Html
{
    public static function beginForm($action = null, $name = null, $method = 'post', $options = [])
    {
        $config = [
            'class' => CSRF::className(),
            'enableCsrfValidation' => false
        ];;
        Container::add('csrf', $config);

        $form = parent::beginForm($action, $method, $name, $options);
        $config = [
            'class' => CSRF::className(),
            'enableCsrfValidation' => true
        ];
        Container::add('csrf', $config);
        return $form;
    }
}