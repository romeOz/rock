<?php

namespace rockunit\core\db\models;

use rock\helpers\Numeric;

/**
 * @property int id
 * @property string username
 * @property string password
 * @property string email
 * @property string token
 * @property int status
 * @property int createdon
 * @property int login_last
 */
class BaseUsers extends ActiveRecord
{
    const S_REGISTRATION = 'registration';

    const STATUS_DELETED = 0;
    const STATUS_BLOCKED = 1;
    const STATUS_NOT_ACTIVE = 2;
    const STATUS_ACTIVE = 3;


//    public function rules()
//    {
//        $timestamp = $this->Rock->date->isoDatetime();
//        return [
//            [
//                self::RULE_DEFAULT,
//                [
//                    'login_last' => $timestamp,
//                    //'createdon' => $timestamp,
//                ],
//                [self::S_REGISTRATION]
//            ]
//        ];
//    }


    public static function tableName()
    {
        return 'users';
    }


    /**
     * @inheritdoc
     * @return BaseUsersQuery
     */
    public static function find()
    {
        return new BaseUsersQuery(get_called_class());
    }



    /**
     * Creates a new user
     *
     * @param  array       $attributes the attributes given by field => value
     * @return BaseUsers|null the newly created model, or null on failure
     */
    public static function create($attributes)
    {
        /** @var Users $user */
        $user = new static();
        $user->setScenario(self::S_REGISTRATION);
        $user->setAttributes($attributes);
        $user->setPassword($attributes['password']);
        $user->setHash(['username', 'email']);
        $user->generateToken();
        $user->setStatus(self::STATUS_NOT_ACTIVE);
        if ($user->save()) {
            return $user;

        } else {
            return null;
        }
    }




    public static function existsByUsernameOrEmail($email, $username)
    {
        return static::find()
            ->orWhere(
                static::tableName() . '.email_hash=UNHEX(MD5(CONCAT(:email, \'' . static::tableName() . '\')))',
                [':email' => $email]
            )
            ->orWhere(
                static::tableName() . '.username_hash=UNHEX(MD5(CONCAT(:username, \'' . static::tableName() . '\')))',
                [':username' => $username]
            )->exists();
    }


    public static function findUrlByUsername($username)
    {
        return static::find()
            ->select(['url'])
            ->byUsername($username)
            ->isEnabled()
            ->asArray()
            ->scalar();
    }


    /**
     * Finds user by id
     *
     * @param int $id
     * @return array|null
     */
    public static function findOneById($id)
    {
        return static::find()
            ->byStatus(self::STATUS_ACTIVE)
            ->byId($id)
            ->asArray()
            ->one();
    }

    /**
     * Finds user by username
     *
     * @param  string      $username
     * @return array|null
     */
    public static function findOneByUsername($username)
    {
        return static::find()
            ->byStatus(self::STATUS_ACTIVE)
            ->byUsername($username)
            ->asArray()
            ->one();
    }


    /**
     * Finds user by email
     *
     * @param  string      $email
     * @return array|null
     */
    public static function findOneByEmail($email)
    {
        return static::find()
            ->byStatus(self::STATUS_ACTIVE)
            ->byEmail($email)
            ->asArray()
            ->one();
    }

    /**
     * Finds user by token
     *
     * @param  string      $token
     * @return UsersQuery|Users|null
     */
    public static function findByToken($token)
    {
        return static::find()
            ->byStatus(self::STATUS_NOT_ACTIVE)
            ->byToken($token)
            //->asArray()
            ->one();
    }

    /**
     * Exists user by id
     *
     * @param  int      $id
     * @return bool
     */
    public static function existsById($id)
    {
        return static::find()
            ->byStatus(self::STATUS_ACTIVE)
            ->byId($id)
            ->exists();
    }


    /**
     * Exists user by username
     *
     * @param  string      $username
     * @return bool
     */
    public static function existsByUsername($username)
    {
        return static::find()
            ->byStatus(self::STATUS_ACTIVE)
            ->byUsername($username)
            ->exists();
    }

    /**
     * Exists user by email
     *
     * @param  string      $email
     * @return bool
     */
    public static function existsByEmail($email)
    {
        return static::find()
            ->byStatus(self::STATUS_ACTIVE)
            ->byEmail($email)
            ->exists();
    }

    /**
     * @param string $username
     * @return int
     */
    public static function deleteByUsername($username)
    {
        $users = static::find();
        return static::deleteAll($users->byUsername($username)->where, $users->params);
    }

    /**
     * Validates password
     *
     * @param  string  $password password to validate
     * @return boolean if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return $this->Rock->security->validatePassword($password, $this->password);
    }

    /**
     *
     * @param int $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }


    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password = $this->Rock->security->generatePasswordHash($password);
    }


    public function setHash(array $attributes)
    {
        foreach ($attributes as $attribute) {
            $this->{$attribute .'_hash'} = Numeric::hexToBin(md5($this->$attribute . static::tableName()));
        }
    }




    /**
     * Generates new password reset token
     */
    public function generateToken()
    {
        $this->token = $this->Rock->security->generateRandomKey() . '_' . time();
    }

    /**
     * Removes password reset token
     */
    public function removeToken()
    {
        $this->token = null;
    }
}