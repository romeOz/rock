<?php

namespace rockunit\core\db\models;

use rock\db\ActiveQuery;
use rock\db\Expression;

class BaseUsersQuery extends ActiveQuery
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
                'id', 'username', 'token', 'status',
                'email', 'login_last'
            ]
        );
    }


    public function fieldsSmall()
    {
        return $this->select(
            [
                'id', 'username'
            ]
        );
    }


    /** WHERE by */


    public function byUsername($username)
    {
        return $this->andWhere(
            static::tableName() . '.username_hash=UNHEX(MD5(CONCAT(:username, \'' . static::tableName() . '\')))',
            [':username' => $username]
        );
    }


    public function byId($id)
    {
        return $this->andWhere([static::tableName() . '.id' => $id]);
    }


    public function byIds(array $ids, $enableOrderByField = false)
    {
        $query = $this
            ->isEnabled()
            ->andWhere([static::tableName() . '.id' => $ids]);
        if ($enableOrderByField === true) {
            $query->addOrderBy([new Expression(' FIELD (' . static::tableName() . '.id, ' . implode(', ', $ids) . ')')]);
        }
        return $query;
    }


    /**
     * @param string $email
     * @return UsersQuery
     */
    public function byEmail($email)
    {
        return $this->andWhere(
            static::tableName() . '.email_hash=UNHEX(MD5(CONCAT(:email, \'' . static::tableName() . '\')))',
            [':email' => $email]
        );
    }

    /**
     * @param string $password
     * @return UsersQuery
     */
    public function byPassword($password)
    {
        return $this->andWhere(
            static::tableName() . '.email_hash=UNHEX(MD5(CONCAT(:email, \'' . static::tableName() . '\')))',
            [':email' => $password]
        );
    }

    /**
     * @param string $token
     * @return UsersQuery
     */
    public function byToken($token)
    {
        return $this->andWhere([static::tableName() . '.token' => $token]);
    }

    /**
     * @param int $status
     * @return UsersQuery
     */
    public function byStatus($status)
    {
        return $this->andWhere([static::tableName() . '.status' => $status]);
    }


    /** WHERE is */

    /**
     * @return UsersQuery
     */
    public function isEnabled()
    {
        return $this->byStatus(Users::STATUS_ACTIVE);
    }



    /**
     * @return static
     */
    public function isDisabled()
    {
        return $this->andWhere(
            '[['.static::tableName() . ']].[[status]] < :status',
            [':status' => Users::STATUS_ACTIVE]
        );
    }
} 