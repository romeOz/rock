<?php

namespace rockunit\extensions\sphinx\models;


use rock\access\Access;

class ArticleFilterIndex extends ArticleIndex
{
    public function beforeFind()
    {
        return parent::beforeFind();
    }
} 