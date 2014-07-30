<?php

namespace rockunit\extensions\sphinx\models;


class RuntimeRulesIndex extends RuntimeIndex
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
                            ->validate($attributes['type_id']) === false
                    ) {
                        return false;
                    }

                    return true;
                }
            ],
        ];
    }
} 