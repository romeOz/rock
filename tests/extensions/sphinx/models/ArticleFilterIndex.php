<?php

namespace rockunit\extensions\sphinx\models;



use rock\access\Access;
use rock\filters\AccessFilter;

class ArticleFilterIndex extends ArticleIndex
{
    public function behaviors()
    {
        return [
            [
                'class' => AccessFilter::className(),
                'rules' =>
                    [
                        'allow' => true,
                        'ips' => ['127.0.0.2'],
                    ],
                'success' =>
                    function (AccessFilter $access) {
                        echo $access->owner instanceof ArticleFilterIndex . 'success';
                    }
                ,
                'fail' =>
                    function (AccessFilter $access) {
                        echo $access->owner instanceof ArticleFilterIndex . 'fail';
                    }


            ],
        ];
    }
} 