<?php

namespace rockunit\extensions\sphinx;


use rock\events\Event;
use rock\helpers\Trace;
use rock\sphinx\ActiveQuery;
use rock\sphinx\Connection;
use rockunit\common\CommonTestTrait;
use rockunit\extensions\sphinx\models\ActiveRecord;
use rockunit\extensions\sphinx\models\ArticleFilterIndex;
use rockunit\extensions\sphinx\models\ArticleIndex;
use rockunit\extensions\sphinx\models\RuntimeIndex;
use rockunit\extensions\sphinx\models\RuntimeRulesIndex;

/**
 * @group search
 * @group sphinx
 * @group db
 */
class ActiveRecordTest extends SphinxTestCase
{
    use CommonTestTrait;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        static::getCache()->flush();
        static::clearRuntime();
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    }


    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();
        static::getCache()->flush();
        static::disableCache();
        static::clearRuntime();
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    }

    protected function setUp()
    {
        parent::setUp();
        ActiveRecord::$db = $this->getConnection();
        Trace::removeAll();
        unset($_POST['_method']);
        Event::offAll();
    }

    protected function tearDown()
    {
        $this->truncateRuntimeIndex('rt_index');
        parent::tearDown();
    }

    public function testFind()
    {
        // find one
        $result = ArticleIndex::find();
        $this->assertTrue($result instanceof ActiveQuery);
        /** @var ArticleIndex $article */
        $article = $result->one();
        $this->assertTrue($article instanceof ArticleIndex);

        // find all
        $articles = ArticleIndex::find()->all();
        $this->assertEquals(2, count($articles));
        $this->assertTrue($articles[0] instanceof ArticleIndex);
        $this->assertTrue($articles[1] instanceof ArticleIndex);

        // find condition
        $article = ArticleIndex::findOne(2);
        $this->assertTrue($article instanceof ArticleIndex);
        $this->assertEquals(2, $article->id);

        // find fulltext
        $article = ArticleIndex::find()->weightTitle()->match('cats')->all();
        $this->assertSame(count($article), 1);
        $this->assertTrue($article[0] instanceof ArticleIndex);
        $this->assertSame(1, $article[0]->id);

        // find by column values
        $article = ArticleIndex::findOne(['id' => 2, 'author_id' => 2]);
        $this->assertTrue($article instanceof ArticleIndex);
        $this->assertEquals(2, $article->id);
        $this->assertEquals(2, $article->author_id);
        $article = ArticleIndex::findOne(['id' => 2, 'author_id' => 1]);
        $this->assertNull($article);

        // find by attributes
        /** @var  ArticleIndex $article */
        $article = ArticleIndex::find()->where(['author_id' => 2])->one();
        $this->assertTrue($article instanceof ArticleIndex);
        $this->assertEquals(2, $article->id);

        // find by comparison
        $article = ArticleIndex::find()->where(['>', 'author_id', 1])->one();
        $this->assertTrue($article instanceof ArticleIndex);
        $this->assertEquals(2, $article->id);

        // find custom column
        $article = ArticleIndex::find()->select(['*', '(5*2) AS custom_column'])
            ->where(['author_id' => 1])->one();
        $this->assertEquals(1, $article->id);
        $this->assertEquals(10, $article->custom_column);

        // find count, sum, average, min, max, scalar
        $this->assertEquals(2, ArticleIndex::find()->count());
        $this->assertEquals(1, ArticleIndex::find()->where('id=1')->count());
        $this->assertEquals(3, ArticleIndex::find()->sum('id'));
        $this->assertEquals(1.5, ArticleIndex::find()->average('id'));
        $this->assertEquals(1, ArticleIndex::find()->min('id'));
        $this->assertEquals(2, ArticleIndex::find()->max('id'));
        $this->assertEquals(2, ArticleIndex::find()->select('COUNT(*)')->scalar());

        // scope
        $this->assertEquals(1, ArticleIndex::find()->favoriteAuthor()->count());

        // asArray
        $article = ArticleIndex::find()->where('id=2')->asArray()->one();
        unset($article['add_date'], $article['category_id']);
        $this->assertEquals([
                                'id' => '2',
                                'author_id' => '2',
                                'tag' => '3,4',
                            ], $article);

        // indexBy
        $articles = ArticleIndex::find()->indexBy('author_id')->orderBy('id DESC')->all();
        $this->assertEquals(2, count($articles));
        $this->assertTrue($articles['1'] instanceof ArticleIndex);
        $this->assertTrue($articles['2'] instanceof ArticleIndex);

        // indexBy callable
        $articles = ArticleIndex::find()->indexBy(function ($article) {
            return $article->id . '-' . $article->author_id;
        })->orderBy('id DESC')->all();
        $this->assertEquals(2, count($articles));
        $this->assertTrue($articles['1-1'] instanceof ArticleIndex);
        $this->assertTrue($articles['2-2'] instanceof ArticleIndex);
    }


    public function testFindBySql()
    {
        // find one
        /** @var  ArticleIndex $article */
        $article = ArticleIndex::findBySql('SELECT * FROM article_index ORDER BY id DESC')->one();
        $this->assertTrue($article instanceof ArticleIndex);
        $this->assertEquals(2, $article->author_id);

        // find all
        $articles = ArticleIndex::findBySql('SELECT * FROM article_index')->all();
        $this->assertEquals(2, count($articles));

        // find with parameter binding
        $article = ArticleIndex::findBySql('SELECT * FROM article_index WHERE id=:id', [':id' => 2])->one();
        $this->assertTrue($article instanceof ArticleIndex);
        $this->assertEquals(2, $article->author_id);
    }

    public function testInsert()
    {
        $record = new RuntimeIndex;
        $record->id = 15;
        $record->title = 'test title';
        $record->content = 'test content';
        $record->type_id = 7;
        $record->category = [1, 2];

        $this->assertTrue($record->isNewRecord);

        $record->save();

        $this->assertEquals(15, $record->id);
        $this->assertFalse($record->isNewRecord);
    }

    /**
     * @depends testInsert
     */
    public function testUpdate()
    {
        $record = new RuntimeIndex;
        $record->id = 2;
        $record->title = 'test title';
        $record->content = 'test content';
        $record->type_id = 7;
        $record->category = [1, 2];
        $record->save();

        // save
        $record = RuntimeIndex::findOne(2);
        $this->assertTrue($record instanceof RuntimeIndex);
        $this->assertEquals(7, $record->type_id);
        $this->assertFalse($record->isNewRecord);

        $record->type_id = 9;
        $record->save();
        $this->assertEquals(9, $record->type_id);
        $this->assertFalse($record->isNewRecord);
        $record2 = RuntimeIndex::findOne(['id' => 2]);
        $this->assertEquals(9, $record2->type_id);

        // replace
        $query = 'replace';
        $rows = RuntimeIndex::find()->match($query)->all();
        $this->assertEmpty($rows);
        $record = RuntimeIndex::findOne(2);
        $record->content = 'Test content with ' . $query;
        $record->save();
        $rows = RuntimeIndex::find()->match($query);
        $this->assertNotEmpty($rows);

        // updateAll
        $pk = ['id' => 2];
        $ret = RuntimeIndex::updateAll(['type_id' => 55], $pk);
        $this->assertEquals(1, $ret);
        $record = RuntimeIndex::findOne($pk);
        $this->assertEquals(55, $record->type_id);
    }

    /**
     * @depends testInsert
     */
    public function testDelete()
    {
        // delete
        $record = new RuntimeIndex;
        $record->id = 2;
        $record->title = 'test title';
        $record->content = 'test content';
        $record->category = [1, 2];
        $record->save();

        $record = RuntimeIndex::findOne(2);
        $record->delete();
        $record = RuntimeIndex::findOne(2);
        $this->assertNull($record);

        // deleteAll
        $record = new RuntimeIndex;
        $record->id = 2;
        $record->title = 'test title';
        $record->content = 'test content';
        $record->type_id = 7;
        $record->category = [1, 2];
        $record->save();

        $ret = RuntimeIndex::deleteAll('id = 2');
        $this->assertEquals(1, $ret);
        $records = RuntimeIndex::find()->all();
        $this->assertEquals(0, count($records));
    }

    public function testCallSnippets()
    {
        $query = 'pencil';
        $source = 'Some data sentence about ' . $query;

        $snippet = ArticleIndex::callSnippets($source, $query);
        $this->assertNotEmpty($snippet, 'Unable to call snippets!');
        $this->assertContains('<b>' . $query . '</b>', $snippet, 'Query not present in the snippet!');

        $rows = ArticleIndex::callSnippets([$source], $query);
        $this->assertNotEmpty($rows, 'Unable to call snippets!');
        $this->assertContains('<b>' . $query . '</b>', $rows[0], 'Query not present in the snippet!');
    }

    public function testCallKeywords()
    {
        $text = 'table pencil';
        $rows = ArticleIndex::callKeywords($text);
        $this->assertNotEmpty($rows, 'Unable to call keywords!');
        $this->assertArrayHasKey('tokenized', $rows[0], 'No tokenized keyword!');
        $this->assertArrayHasKey('normalized', $rows[0], 'No normalized keyword!');
    }


    public function testCache()
    {
        if (!interface_exists('\rock\cache\CacheInterface') || !class_exists('\League\Flysystem\Filesystem')) {
            $this->markTestSkipped('Rock cache not installed.');
        }

        $cache = static::getCache();
        $cache->flush();

        /* @var $connection Connection */
        $connection = $this->getConnection();
        Trace::removeAll();

        $connection->enableQueryCache = true;
        $connection->queryCache = $cache;

        // all
        ArticleIndex::find()->where(['id' => 1])->with('category')->asArray()->all($connection);
        $trace = Trace::getIterator('db.query');
        $this->assertFalse($trace->current()['cache']);
        $trace->next();
        $this->assertFalse($trace->current()['cache']);
        ArticleIndex::find()->where(['id' => 1])->with('category')->asArray()->all($connection);
        $trace = Trace::getIterator('db.query');
        $this->assertTrue($trace->current()['cache']);
        $trace->next();
        $this->assertTrue($trace->current()['cache']);
        ArticleIndex::find()->where(['id' => 1])->with('category')->endCache()->asArray()->all($connection);
        $trace = Trace::getIterator('db.query');
        $this->assertFalse($trace->current()['cache']);
        $trace->next();
        $this->assertFalse($trace->current()['cache']);

        /* @var $connection Connection */
        $connection->enableQueryCache = true;
        $connection->queryCache = $cache;
        ArticleIndex::find()
           ->where(['id' => 1])
           ->with(
               ['category'=> function(ActiveQuery $query){$query->endCache();}]
           )
           ->asArray()
           ->all($connection);
        $trace = Trace::getIterator('db.query');
        $this->assertTrue($trace->current()['cache']);
        $this->assertSame($trace->current()['count'], 4);
        $trace->next();
        $this->assertFalse($trace->current()['cache']);
        $this->assertSame($trace->current()['count'], 4);

        $cache->flush();
        Trace::removeAll();
        /* @var $connection Connection */
        $connection->enableQueryCache = true;
        $connection->queryCache = $cache;

        // one
        ArticleIndex::find()->where(['id' => 1])->with('category')->asArray()->one($connection);
        $trace = Trace::getIterator('db.query');
        $this->assertFalse($trace->current()['cache']);
        $trace->next();
        $this->assertFalse($trace->current()['cache']);
        ArticleIndex::find()->where(['id' => 1])->with('category')->asArray()->one($connection);
        $trace = Trace::getIterator('db.query');
        $this->assertTrue($trace->current()['cache']);
        $trace->next();
        $this->assertTrue($trace->current()['cache']);

        $connection->enableQueryCache = false;
        $connection->queryCache = $cache;
        ArticleIndex::find()
            ->where(['id' => 1])
            ->with(
                ['category'=> function(ActiveQuery $query){$query->endCache();}]
            )
            ->beginCache()
            ->asArray()
            ->one($connection);
        $trace = Trace::getIterator('db.query');
        $this->assertTrue($trace->current()['cache']);
        $trace->next();
        $this->assertFalse($trace->current()['cache']);

        $cache->flush();
        Trace::removeAll();
        $connection->enableQueryCache = true;
        $connection->queryCache = $cache;

        //as ActiveRecord
        /* @var $connection Connection */
        ArticleIndex::find()->where(['id' => 1])->with('category')->all($connection);
        $trace = Trace::getIterator('db.query');
        $this->assertFalse($trace->current()['cache']);
//        $trace->next();
//        $trace->next();
        $trace->next();
        $this->assertFalse($trace->current()['cache']);
        ArticleIndex::find()->where(['id' => 1])->with('category')->all($connection);
        $trace = Trace::getIterator('db.query');
        $this->assertTrue($trace->current()['cache']);
//        $trace->next();
//        $trace->next();
        $trace->next();
        $this->assertTrue($trace->current()['cache']);
        ArticleIndex::find()
            ->where(['id' => 1])
            ->with( ['category'=> function(ActiveQuery $query){$query->endCache();}])
            ->all($connection);
        $trace = Trace::getIterator('db.query');
        $this->assertTrue($trace->current()['cache']);
//        $trace->next();
//        $trace->next();
        $trace->next();
        $this->assertFalse($trace->current()['cache']);

        $cache->flush();
        Trace::removeAll();
        $connection->enableQueryCache = true;
        $connection->queryCache = $cache;

        // expire
        $connection->queryCacheExpire = 1;
        ArticleIndex::find()->where(['id' => 1])->with('category')->asArray()->one($connection);
        $trace = Trace::getIterator('db.query');
        $this->assertFalse($trace->current()['cache']);
        $trace->next();
        $this->assertFalse($trace->current()['cache']);
        sleep(3);
        ArticleIndex::find()->where(['id' => 1])->with('category')->asArray()->one($connection);
        $trace = Trace::getIterator('db.query');
        $this->assertFalse($trace->current()['cache']);
        $trace->next();
        $this->assertFalse($trace->current()['cache']);

        Trace::removeAll();
        $cache->flush();
        $connection->queryCacheExpire = 0;
        $connection->enableQueryCache = true;
        $connection->queryCache = $cache;

        // beginCache and endCache
        $connection->enableQueryCache = false;
        ActiveRecord::$db = $connection;
        ArticleIndex::find()
            ->where(['id' => 1])
            ->with( ['category'=> function(ActiveQuery $query){$query->beginCache();}])
            ->asArray()
            ->all();
        $trace = Trace::getIterator('db.query');
        $this->assertFalse($trace->current()['cache']);
        $trace->next();
        $this->assertFalse($trace->current()['cache']);
        ArticleIndex::find()
            ->where(['id' => 1])
            ->with( ['category'=> function(ActiveQuery $query){$query->beginCache();}])
            ->asArray()
            ->all();
        $trace = Trace::getIterator('db.query');
        $this->assertFalse($trace->current()['cache']);
        $trace->next();
        $this->assertTrue($trace->current()['cache']);

        static::disableCache();
    }

    public function testBeforeFind()
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        // fail
        $query = ArticleFilterIndex::find()
            ->where(['id' => 1]);
        $this->assertEmpty($query->one());

        // success
        $_SERVER['REMOTE_ADDR'] = '127.0.0.2';
        $query = ArticleFilterIndex::find()
            ->where(['id' => 1]);
        $this->assertEquals($query->one()->author_id, 1);
        $this->assertEmpty(Event::getAll());
        $this->expectOutputString('1fail1success');
    }


    public function testInsertWithRule()
    {
        // fail
        $runtime = new  RuntimeRulesIndex();
        $runtime->id = 15;
        $runtime->title = 'test title';
        $runtime->content = 'test content';
        $runtime->type_id = 'test';
        $runtime->category = [1, 2];
        $this->assertFalse($runtime->save());
        $this->assertNotEmpty($runtime->getErrors());

        // success
        $runtime = new RuntimeRulesIndex();
        $runtime->id = 15;
        $runtime->title = 'test title';
        $runtime->content = 'test content';
        $runtime->type_id = 7;
        $runtime->category = [1, 2];
        $this->assertTrue($runtime->save());
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/4830
     *
     * @depends testFind
     */
    public function testFindQueryReuse()
    {
        $result = ArticleIndex::find()->andWhere(['author_id' => 1]);
        $this->assertTrue($result->one() instanceof ArticleIndex);
        $this->assertTrue($result->one() instanceof ArticleIndex);

        $result = ArticleIndex::find()->match('dogs');
        $this->assertTrue($result->one() instanceof ArticleIndex);
        $this->assertTrue($result->one() instanceof ArticleIndex);
    }
}