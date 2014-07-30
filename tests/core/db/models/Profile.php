<?php
namespace rockunit\core\db\models;

/**
 * Class Profile
 *
 * @property integer $id
 * @property string $description
 *
 */
class Profile extends ActiveRecord
{
    public static function tableName()
    {
        return 'profile';
    }

//    public function attributes()
//    {
//        return array_merge(parent::attributes(), ['name']);
//    }
}
