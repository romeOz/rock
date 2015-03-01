<?php

namespace apps\frontend\controllers;


use rock\core\Controller;

class MainController extends Controller
{
    public function actionIndex()
    {
        $this->render('index', ['content' => 'Hello world!']);
    }

    public function notPage()
    {
        return parent::notPage(null, 'index');
    }
} 