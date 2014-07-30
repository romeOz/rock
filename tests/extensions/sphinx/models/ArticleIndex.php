<?php
namespace rockunit\extensions\sphinx\models;


/**
 * Class ArticleIndex
 *
 * @property integer $id
 * @property string $title
 * @property string $content
 * @property integer $category_id
 * @property integer $author_id
 * @property integer $create_date
 *
 */
class ArticleIndex extends ActiveRecord
{
    public $custom_column;

    /**
     * @inheritdoc
     */
    public static function indexName()
    {
        return 'article_index';
    }

    public function getSource()
    {
        return $this->hasOne(ArticleDb::className(), ['id' => 'id']);
    }

    public function getCategory()
    {
        return $this->hasOne(CategoryIndex::className(), ['id' => 'category_id']);
    }

    public function getSourceCompositeLink()
    {
        return $this->hasOne(ArticleDb::className(), ['id' => 'id', 'author_id' => 'author_id']);
    }

    public function getTags()
    {
        return $this->hasMany(TagDb::className(), ['id' => 'tag']);
    }

    /**
     * @inheritdoc
     */
    public function getSnippetSource()
    {
        return $this->source->content;
    }

    /**
     * @return ArticleIndexQuery
     */
    public static function find()
    {
        return new ArticleIndexQuery(get_called_class());
    }
}
