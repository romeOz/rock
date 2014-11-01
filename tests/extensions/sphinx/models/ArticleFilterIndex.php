<?php

namespace rockunit\extensions\sphinx\models;


use rock\access\Access;

class ArticleFilterIndex extends ArticleIndex
{
    public function beforeFind()
    {
        $this->checkAccess(
            [
                'allow' => true,
                'verbs' => ['POST'],
            ],
            [
                function (Access $access) {
                    echo $access->owner instanceof ArticleFilterIndex . 'success';
                }
            ],
            [
                function (Access $access) {
                    echo $access->owner instanceof ArticleFilterIndex . 'fail';
                }
            ]
        );

        return parent::beforeFind();
    }
} 