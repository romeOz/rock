<?php

namespace rockunit\core\db\models;


class UsersQuery extends BaseUsersQuery
{

    public static function tableName()
    {
        return Users::tableName();
    }

    /** Fields */
    public function fields()
    {
        return $this->select(
            [
                'id', 'username', 'firstname', 'lastname', 'CONCAT('.static::tableName().'.firstname," ",'.static::tableName().'.lastname) AS fullname', 'dob', 'gender', 'status',
                'email', 'login_last', 'url'
            ]
        );
    }

    public function fieldsSmall()
    {
        return $this->select(
            [
                'id', 'username', 'firstname', 'lastname', 'CONCAT('.static::tableName().'.firstname," ",'.static::tableName().'.lastname) AS fullname',  'url'
            ]
        );
    }


    /** WHERE by */
    /**
     * @param string $url
     * @return UsersQuery
     */
    public function byUrl($url)
    {
        return $this->andWhere(
            static::tableName() . '.url_hash=UNHEX(MD5(CONCAT(:url_hash, \'' . static::tableName() . '\')))',
            [':url_hash' => $url]
        );
    }
} 