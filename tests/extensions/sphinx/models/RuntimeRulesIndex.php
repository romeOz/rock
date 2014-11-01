<?php

namespace rockunit\extensions\sphinx\models;


class RuntimeRulesIndex extends RuntimeIndex
{
    public function rules()
    {
        return [
            [
                self::RULE_VALIDATE, 'type_id', 'required', 'int'
            ],
        ];
    }
} 