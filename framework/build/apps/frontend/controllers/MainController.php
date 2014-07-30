<?php

namespace apps\frontend\controllers;


use rock\base\Controller;

class MainController extends Controller
{
    public function actionIndex()
    {
        $this->render('index', ['content' => 'Hello world!']);
    }
} 