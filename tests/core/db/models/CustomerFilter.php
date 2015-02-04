<?php
namespace rockunit\core\db\models;


use rock\access\Access;
use rock\filters\AccessFilter;
use rock\filters\VerbFilter;

class CustomerFilter extends Customer
{
    public function behaviors()
    {
        return [
            [
                'class' => VerbFilter::className(),
                'actions' =>
                    [
                        '*' => ['POST']
                    ],
                'success' => [
                    function (VerbFilter $verb) {
                        echo $verb->owner instanceof CustomerFilter . 'success';
                    }
                ],
                'fail' => [
                    function (VerbFilter $verb) {
                        echo $verb->owner instanceof CustomerFilter . 'fail';
                    }
                ]
            ],
        ];
    }
}
