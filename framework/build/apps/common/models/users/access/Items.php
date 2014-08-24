<?php

namespace apps\common\models\users\access;

/**
 * @property string $name
 * @property int $type
 * @property string $description
 * @property string $class
 * @property string $data
 */
class Items extends BaseItems
{
    /**
     * @inheritdoc
     * @return ItemsQuery
     */
    public static function find()
    {
        return new ItemsQuery(get_called_class());
    }
}