<?php

namespace rockunit\core\template\controllers;


use rock\core\Controller;

class TestController extends Controller
{
    public static function defaultData()
    {
        return [
            'context' => [
                'url'   => '/test/',
            ]
        ];
    }
}