<?php

namespace rockunit\extensions\sphinx\models;

/**
 * Class CategoryIndex
 *
 * @property integer $id
 * @property string $title
 * @property float $price
 */
class CategoryIndex extends ActiveRecord
{
    public static function indexName()
    {
        return 'category_index';
    }
}
