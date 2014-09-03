<?php

namespace apps\common\controllers;


use rock\base\Controller;

class BaseCaptchaController extends Controller
{
    /**
     * Action Index
     */
    public function actionIndex()
    {
        $captcha = $this->Rock->captcha;
        $captcha->display();
    }
} 