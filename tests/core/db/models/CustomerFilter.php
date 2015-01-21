<?php
namespace rockunit\core\db\models;


use rock\access\Access;

class CustomerFilter extends Customer
{
    public function beforeFind()
    {
        return parent::beforeFind();
    }
}
