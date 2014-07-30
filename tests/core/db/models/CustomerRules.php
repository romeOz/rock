<?php
namespace rockunit\core\db\models;


class CustomerRules extends Customer
{
    public function rules()
    {
        return [
            [
                self::RULE_VALIDATION,
                function (array $attributes) {
                    if ($this->Rock->validation
                            ->notEmpty()
                            ->int()
                            ->setPlaceholders('e_test')
                            ->validate($attributes['name']) === false
                    ) {
                        return false;
                    }

                    return true;
                }
            ],
        ];
    }
}
