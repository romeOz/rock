<?php

namespace apps\common\models\users;

use apps\frontend\controllers\users\UsersController;
use apps\frontend\models\geo\Cities;
use apps\frontend\models\geo\Countries;
use apps\frontend\models\geo\Regions;
use apps\frontend\models\templates\Templates;
use apps\frontend\models\users\ImagesUsers;
use rock\db\SelectBuilder;
use rock\helpers\String;


/**
 * @property int dob
 * @property string firstname
 * @property string lastname
 * @property int gender
 * @property string url
 */
class Users extends BaseUsers
{

    public function rules()
    {
        //$timestamp = time();
        return [
            [
                self::RULE_DEFAULT,
                [
                    //'login_last' => $timestamp,
                    //'createdon' => $timestamp,
                    'firstname' => null,
                    'lastname' => null,
                    'gender' => 0,
                    'city_id' => null,
                    'region_id' => null,
                    'country_id' => null,
                    //                    'url' => function(array $attributes){
                    //                            return $this->setUrl($attributes['username']);
                    //                        },
                    //                    'url_hash' => function(array $attributes){
                    //                            return isset($attributes['url']) ?  Numeric::hexToBin(md5($attributes['url'] . static::tableName())) : '';
                    //                        }
                ],
                [self::S_REGISTRATION]
            ]
        ];
    }


    /**
     * @inheritdoc
     * @return UsersQuery
     */
    public static function find()
    {
        return new UsersQuery(get_called_class());
    }


    /**
     * Relations
     */
    public function getTemplate()
    {
        return $this->hasOne(Templates::className(), ['id' => 'template_id']);
    }

    public function getCountry()
    {
        return $this->hasOne(Countries::className(), ['id' => 'country_id']);
    }


    public function getRegions()
    {
        return $this->hasOne(Regions::className(), ['id' => 'region_id']);
    }

    public function getCity()
    {
        return $this->hasOne(Cities::className(), ['id' => 'city_id']);
    }

    public function getAvatar()
    {
        return $this->hasOne(ImagesUsers::className(), ['id' => 'avatar_id']);
    }



    /**
     * Get data user by url
     *
     * @param string        $url - url of user
     * @return array|null
     */
    public static function findOneByUrl($url)
    {
        return static::find()
            ->select(new SelectBuilder([
                                           static::find()->fields(),
                                           ImagesUsers::find()->fieldImage(),
                                           [Countries::find()->fieldName(), false],
                                           [Regions::find()->fieldName(), false],
                                           [Cities::find()->fieldName(), false]
                                       ])
            )
            ->joinWith([/*'template', */'country', 'regions', 'city', 'avatar'], false)
            ->byUrl($url)
            ->isEnabled()
            ->asArray()
            ->one(null, true);
    }


    /**
     * Get data user by id
     *
     * @param string        $id - id of user
     * @return array|null
     */
    public static function findOneById($id)
    {
        return static::find()
            ->select(new SelectBuilder([
                                           static::find()->fields(),
                                           ImagesUsers::find()->fieldImage(),
                                           [Countries::find()->fieldName(), false],
                                           [Regions::find()->fieldName(), false],
                                           [Cities::find()->fieldName(), false]
                                       ])
            )
            ->joinWith(['country', 'regions', 'city', 'avatar'], false)
            ->byId($id)
            ->isEnabled()
            ->asArray()
            ->one(null, true);
    }


    /**
     * Get data user by username
     *
     * @param string        $username - username of user
     * @return array|null
     */
    public static function findOneByUsername($username)
    {
        return static::find()
            ->select(new SelectBuilder([
                                           static::find()->fields(),
                                           ImagesUsers::find()->fieldImage(),
                                           [Countries::find()->fieldName(), false],
                                           [Regions::find()->fieldName(), false],
                                           [Cities::find()->fieldName(), false]
                                       ])
            )
            ->joinWith(['country', 'regions', 'city', 'avatar'], false)
            ->byUsername($username)
            ->isEnabled()
            ->asArray()
            ->one(null, true);
    }

    /**
     * Get data user by email
     *
     * @param string $email - email of user
     * @return array|null
     */
    public static function findOneByEmail($email)
    {
        return static::find()
            ->select(new SelectBuilder([
                                           static::find()->fields(),
                                           ImagesUsers::find()->fieldImage(),
                                           [Countries::find()->fieldName(), false],
                                           [Regions::find()->fieldName(), false],
                                           [Cities::find()->fieldName(), false]
                                       ])
            )
            ->joinWith([/*'template', */'country', 'regions', 'city', 'avatar'], false)
            ->byEmail($email)
            ->isEnabled()
            ->asArray()
            ->one(null, true);
    }




    public static function findUrlById($id)
    {
        return static::find()
            ->select(['url'])
            ->byId($id)
            ->isEnabled()
            ->asArray()
            ->beginCache()
            ->scalar();
    }

    public static function findUrlByUsername($username)
    {

        return static::find()
            ->select(['url'])
            ->byUsername($username)
            ->isEnabled()
            ->asArray()
            ->beginCache()
            ->scalar();
    }

    /**
     * Creates a new user
     *
     * @param  array       $attributes the attributes given by field => value
     * @return Users|null the newly created model, or null on failure
     */
    public static function create($attributes)
    {
        /** @var Users $user */
        $user = new static();
        $user->setScenario(self::S_REGISTRATION);

        $user->setAttributes($attributes);
        $user->setPassword($attributes['password']);
        $user->toTimestamp($attributes['dob']);
        $user->setUrl($attributes['username']);
        $user->setHash(['username', 'email', 'url']);
        $user->generateToken();
        $user->setStatus(self::STATUS_NOT_ACTIVE);
        if ($user->save()) {
            return $user;

        } else {
            return null;
        }
    }


    public function safeAttributes()
    {
        return ['email', 'password', 'username', 'gender', 'firstname', 'lastname', 'dob', 'city_id', 'country_id', 'region_id'];
    }

    public function toTimestamp($dob)
    {
        if (!empty($this->dob)) {
            $this->dob = strtotime($dob);
        }
    }

    public function setUrl($username)
    {
       $this->url = mb_strtolower(
            UsersController::context(['url']) . static::translitUsername($username) . '/',
            'UTF-8'
        );
    }

    /**
     * Translit username
     *
     * @param string $username
     * @return string
     */
    protected static function translitUsername($username)
    {
        return strtolower(
            String::translit(
                preg_replace(
                    [
                        '/[^\\w\\s\-]+/iu',
                        '/\\s+/iu',
                        '/[\_\-]+/iu'
                    ],
                    ['', '-', '-'],
                    $username
                )
            )
        );
    }
}