<?php

namespace rockunit\extensions\sphinx;

use rockunit\extensions\sphinx\models\ActiveRecord;
use rockunit\core\db\models\ActiveRecord as ActiveRecordDb;
use rockunit\extensions\sphinx\models\ArticleDb;
use rockunit\extensions\sphinx\models\ArticleIndex;

/**
 * @group search
 * @group sphinx
 * @group db
 */
class ActiveRelationTest extends SphinxTestCase
{
    protected function setUp()
    {
        parent::setUp();
        ActiveRecord::$db = $this->getConnection();
        ActiveRecordDb::$db = $this->getDbConnection();
    }

    // Tests :

    public function testFindLazy()
    {
        /* @var $article ArticleDb */
        $article = ArticleDb::findOne(['id' => 2]);
        $this->assertFalse($article->isRelationPopulated('index'));
        $index = $article->index;
        $this->assertTrue($article->isRelationPopulated('index'));
        $this->assertTrue($index instanceof ArticleIndex);
        $this->assertEquals(1, count($article->relatedRecords));
        $this->assertEquals($article->id, $index->id);
    }

    public function testFindEager()
    {
        $articles = ArticleDb::find()->with('index')->all();
        $this->assertEquals(2, count($articles));
        $this->assertTrue($articles[0]->isRelationPopulated('index'));
        $this->assertTrue($articles[1]->isRelationPopulated('index'));
        $this->assertTrue($articles[0]->index instanceof ArticleIndex);
        $this->assertTrue($articles[1]->index instanceof ArticleIndex);
        $this->assertSame($articles[1]->id, $articles[1]->index->id);
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/4018
     */
    public function testFindCompositeLink()
    {
        $articles = ArticleIndex::find()->with('sourceCompositeLink')->all();
        $this->assertEquals(2, count($articles));
        $this->assertTrue($articles[0]->isRelationPopulated('sourceCompositeLink'));
        $this->assertNotEmpty($articles[0]->sourceCompositeLink);
        $this->assertTrue($articles[1]->isRelationPopulated('sourceCompositeLink'));
        $this->assertNotEmpty($articles[1]->sourceCompositeLink);
        $this->assertSame(
            ArticleIndex::find()->match('about')->with('sourceCompositeLink', 'source')->snippetByModel()->all()[0]->getSnippet(),
            'This article is <b>about</b> cats'
        );
    }

}
