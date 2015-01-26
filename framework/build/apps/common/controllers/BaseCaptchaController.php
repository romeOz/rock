<?php

namespace apps\common\controllers;


use rock\core\Controller;
use rock\Rock;

class BaseCaptchaController extends Controller
{
    /**
     * Action Index
     */
    public function actionIndex()
    {
        Rock::$app->captcha->display();
    }
} 