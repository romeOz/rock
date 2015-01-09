<?php

namespace rockunit\extensions\mongodb\models\file;

class CustomerFile extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function collectionName()
    {
        return 'customer_fs';
    }

    /**
     * @inheritdoc
     */
    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            [
                'tag',
                'status',
            ]
        );
    }

    /**
     * @inheritdoc
     * @return CustomerFileQuery
     */
    public static function find()
    {
        return new CustomerFileQuery(get_called_class());
    }
}
