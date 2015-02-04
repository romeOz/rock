<?php
namespace rockunit\core\db\models;


use rock\access\Access;
use rock\filters\AccessFilter;

class CustomerFilter extends Customer
{
    public function behaviors()
    {
        return [
            [
                'class' => AccessFilter::className(),
                'rules' =>
                    [
                        'allow' => true,
                        'ips' => ['127.0.0.5'],
                    ],
                'success' => [
                    function (Access $access) {
                        echo $access->owner instanceof CustomerFilter . 'success';
                    }
                ],
                'fail' => [
                    function (Access $access) {
                        echo $access->owner instanceof CustomerFilter . 'fail';
                    }
                ]
            ],
        ];
    }
}
