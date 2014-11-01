<?php
namespace rockunit\core\db\models;


use rock\access\Access;

class CustomerFilter extends Customer
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
                    echo $access->owner instanceof CustomerFilter . 'success';
                }
            ],
            [
                function (Access $access) {
                    echo $access->owner instanceof CustomerFilter . 'fail';
                }
            ]
        );

        return parent::beforeFind();
    }
}
