<?php

namespace rockunit\extensions\sphinx;

use rock\sphinx\ActiveDataProvider;
use rockunit\extensions\sphinx\models\ActiveRecord;
use rockunit\extensions\sphinx\models\ArticleDb;
use rockunit\extensions\sphinx\models\ArticleIndex;

/**
 * @group search
 * @group sphinx
 * @group db
 */
class ActiveDataProviderTest extends SphinxTestCase
{
    protected function setUp()
    {
        parent::setUp();
        ActiveRecord::$db = $this->getConnection(false);
    }


    protected $optionsSnippet = [
        'limit' => 1000,
        'before_match' => '<span>',
        'after_match' => '</span>'
    ];

    public function testQuery()
    {

        $config = [
            'query' => (new \rock\db\Query())->from('sphinx_article'),
            'model' => ArticleIndex::className(),
            'callSnippets' => [
                'content' =>
                    [
                        'about',
                        $this->optionsSnippet
                    ],

            ],
            'pagination' => ['limit' => 1, 'sort' => SORT_DESC]
        ];
        $provider = (new ActiveDataProvider($config));
        $this->assertSame(
            $provider->get($this->getDbConnection(false))[0]['content'],
            'This article is <span>about</span> cats'
        );
        $this->assertSame(count($provider->get()), 1);
        $this->assertNotEmpty($provider->getPagination());
        $this->assertSame($provider->getTotalCount(), 2);
    }

    public function testActiveQuery()
    {
        ArticleDb::$db = $this->getDbConnection(false);
        $provider = new ActiveDataProvider([
            'query' => ArticleDb::find()->orderBy('id ASC')->asArray(),
            'model' => ArticleIndex::className(),
            'callSnippets' => [
               'content' =>
                   [
                       'about',
                       $this->optionsSnippet
                   ],

            ],
            'pagination' => ['limit' => 1, 'sort' => SORT_DESC]
        ]);
        $this->assertSame(
            $provider->get()[0]['content'],
            'This article is <span>about</span> cats'
        );
        $this->assertSame(count($provider->get()), 1);
        $this->assertNotEmpty($provider->getPagination());
        $this->assertSame($provider->getTotalCount(), 2);

        $provider = new ActiveDataProvider([
           'query' => ArticleDb::find()->orderBy('id ASC')->indexBy('id'),
           'model' => ArticleIndex::className(),
           'callSnippets' => [
               'content' =>
                   [
                       'about',
                       $this->optionsSnippet
                   ],

           ],
           'pagination' => ['limit' => 1, 'sort' => SORT_DESC]
        ]);

        $this->assertSame(
            $provider->get()[1]['content'],
            'This article is <span>about</span> cats'
        );
        $this->assertSame(count($provider->get()), 1);
        $this->assertNotEmpty($provider->getPagination());
        $this->assertSame($provider->getTotalCount(), 2);

        $provider = new ActiveDataProvider([
           'query' => ArticleIndex::find()->match('about')->with('sourceCompositeLink')->indexBy('id'),
           //'model' => ArticleIndex::className(),
           'with' => 'sourceCompositeLink',
           'callSnippets' => [
               'content' =>
                   [
                       'about',
                       $this->optionsSnippet
                   ],

           ],
           'pagination' => ['limit' => 1, 'sort' => SORT_DESC]
        ]);

        $this->assertSame(
            $provider->get()[1]['sourceCompositeLink']['content'],
            'This article is <span>about</span> cats'
        );

        $this->assertSame(count($provider->get()), 1);
        $this->assertNotEmpty($provider->getPagination());
        $this->assertSame($provider->getTotalCount(), 2);
    }

    public function testArray()
    {
        ArticleDb::$db = $this->getDbConnection(false);
        $provider = new ActiveDataProvider([
           'query' => ArticleIndex::find()->match('about')->with('sourceCompositeLink')->indexBy('id')->asArray()->all(),
           'model' => ArticleIndex::className(),
           'with' => 'sourceCompositeLink',
           'only' => ['sourceCompositeLink'],
           'callSnippets' => [
               'content' =>
                   [
                       'about',
                       $this->optionsSnippet
                   ],

           ],
           'pagination' => ['limit' => 1, 'sort' => SORT_DESC]
        ]);

        $this->assertSame(
            $provider->get()[1]['sourceCompositeLink']['content'],
            'This article is <span>about</span> cats'
        );

        $this->assertSame(count($provider->get()), 1);
        $this->assertNotEmpty($provider->getPagination());
        $this->assertSame($provider->getTotalCount(), 2);
        $this->assertSame(count(current($provider->toArray())),1);
    }
}
