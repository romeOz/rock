<?php

namespace apps\frontend\controllers;


use rock\core\Controller;

class MainController extends Controller
{
    public function actionIndex()
    {
        $this->render('index', ['content' => 'Hello world!']);
    }

    public function notPage($layout = 'index')
    {
        return parent::notPage($layout);
    }
} 