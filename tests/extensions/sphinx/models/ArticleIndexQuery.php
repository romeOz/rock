<?php

namespace rockunit\extensions\sphinx\models;

use rock\sphinx\ActiveQuery;

/**
 * ArticleIndexQuery
 */
class ArticleIndexQuery extends ActiveQuery
{
    public function fields()
    {
        return $this->select(['id', 'category', 'create_date']);
    }
    public function weightTitle($weight = 13)
    {
        return $this->options(['field_weights' => ['title' => $weight]]);
    }

    public function favoriteAuthor()
    {
        $this->andWhere('author_id=1');

        return $this;
    }
}
