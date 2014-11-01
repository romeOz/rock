<?php
namespace rockunit\core\db\models;


class CustomerRules extends Customer
{
    public function rules()
    {
        return [
            [
                self::RULE_VALIDATE, 'name', 'required', 'int'
            ],
        ];
    }
}
