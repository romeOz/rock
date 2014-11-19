<?php

namespace rock\behaviors;

use rock\db\BaseActiveRecord;
use rock\db\Expression;

/**
 * TimestampBehavior automatically fills the specified attributes with the current timestamp.
 *
 * To use TimestampBehavior, insert the following code to your ActiveRecord class:
 *
 * ```php
 * use yii\behaviors\TimestampBehavior;
 *
 * public function behaviors()
 * {
 *   return [
 *       TimestampBehavior::className(),
 *   ];
 * }
 * ```
 *
 * By default, TimestampBehavior will fill the `created_at` and `updated_at` attributes with the current timestamp
 * when the associated AR object is being inserted; it will fill the `updated_at` attribute
 * with the timestamp when the AR object is being updated. The timestamp value is obtained by `time()`.
 *
 * If your attribute names are different or you want to use a different way of calculating the timestamp,
 * you may configure the {@see \rock\behaviors\TimestampBehavior::$createdAtAttribute}, {@see \rock\behaviors\TimestampBehavior::$updatedAtAttribute}
 * and {@see \rock\behaviors\TimestampBehavior::$value} properties like the following:
 *
 * ```php
 * use yii\db\Expression;
 *
 * public function behaviors()
 * {
 *   return [
 *       [
 *           'class' => TimestampBehavior::className(),
 *           'createdAtAttribute' => 'create_time',
 *           'updatedAtAttribute' => 'update_time',
 *           'value' => new Expression('NOW()'),
 *       ],
 *   ];
 * }
 * ```
 *
 * TimestampBehavior also provides a method named {@see  \rock\behaviors\TimestampBehavior::touch()} that allows you to assign the current
 * timestamp to the specified attribute(s) and save them to the database. For example,
 *
 * ```php
 * $this->timestamp->touch('creation_time');
 * ```
 */
class TimestampBehavior extends AttributeBehavior
{
    public $attributes = [];

    /**
     * @var string the attribute that will receive timestamp value
     * Set this property to false if you do not want to record the creation time.
     */
    public $createdAtAttribute = 'ctime';
    /**
     * @var string the attribute that will receive timestamp value.
     * Set this property to false if you do not want to record the update time.
     */
    public $updatedAtAttribute = 'mtime';
    /**
     * @var callable|Expression The expression that will be used for generating the timestamp.
     * This can be either an anonymous function that returns the timestamp value,
     * or an {@see \rock\db\Expression} object representing a DB expression (e.g. `new Expression('NOW()')`).
     * If not set, it will use the value of `time()` to set the attributes.
     */
    public $value;


    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        if (empty($this->attributes)) {
            $this->attributes = [
                BaseActiveRecord::EVENT_BEFORE_INSERT => [$this->createdAtAttribute, $this->updatedAtAttribute],
                BaseActiveRecord::EVENT_BEFORE_UPDATE => $this->updatedAtAttribute,
            ];
        }
    }

    /**
     * Updates a timestamp attribute to the current timestamp.
     *
     * ```php
     * $model->touch('lastVisit');
     * ```
     *
     * @param string $attribute the name of the attribute to update.
     */
    public function touch($attribute)
    {
        $this->owner->updateAttributes(array_fill_keys((array)$attribute, $this->getValue(null)));
    }

    /**
     * @inheritdoc
     */
    protected function getValue($event)
    {
        if ($this->value instanceof Expression) {
            return $this->value;
        } else {
            return $this->value !== null ? call_user_func($this->value, $event) : time();
        }
    }
}