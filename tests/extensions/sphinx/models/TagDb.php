<?php

namespace rockunit\extensions\sphinx\models;


class TagDb extends \rockunit\core\db\models\ActiveRecord
{
    public static function tableName()
    {
        return 'sphinx_tag';
    }
}
