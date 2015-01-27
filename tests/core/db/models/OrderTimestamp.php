<?php

namespace rockunit\core\db\models;

use rock\behaviors\TimestampBehavior;
use rock\components\ModelEvent;

/**
 * Class Order
 *
 * @property integer $id
 * @property integer $customer_id
 * @property integer $created_at
 * @property string $total
 */
class OrderTimestamp extends Order
{
    public function behaviors()
    {
        return [
           [
               'class' => TimestampBehavior::className(),
               'createdAtAttribute' => 'created_at',
               'updatedAtAttribute' => 'created_at',
           ],
        ];
    }

    public function beforeSave($insert)
    {
        $event = new ModelEvent;
        $this->trigger($insert ? self::EVENT_BEFORE_INSERT : self::EVENT_BEFORE_UPDATE, $event);
        return $event->isValid;
    }
}
