<?php

namespace rockunit\extensions\sphinx\models;

/**
 * Class RuntimeIndex
 *
 * @property integer $id
 * @property string $title
 * @property string $content
 * @property integer $type_id
 * @property array $category
 */
class RuntimeIndex extends ActiveRecord
{
    public static function indexName()
    {
        return 'rt_index';
    }
}
