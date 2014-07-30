<?php

namespace rockunit\extensions\sphinx\models;

class CategoryDb extends \rockunit\core\db\models\ActiveRecord
{
    public static function tableName()
    {
        return 'sphinx_category';
    }
}
