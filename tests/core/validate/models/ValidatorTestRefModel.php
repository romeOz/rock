<?php

namespace rockunit\core\validate\models;

use rockunit\core\db\models\ActiveRecord;

class ValidatorTestRefModel extends ActiveRecord
{

    public $test_val = 2;
    public $test_val_fail = 99;

    public static function tableName()
    {
        return 'validator_ref';
    }

    public function getMain()
    {
        return $this->hasOne(ValidatorTestMainModel::className(), ['id' => 'ref']);
    }
}
