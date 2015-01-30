<?php

namespace rockunit\snippets;

use rock\helpers\Pagination;
use rock\Rock;
use rock\snippets\ListView;
use rock\template\Template;
use rockunit\core\template\TemplateCommon;

class ListViewTest extends TemplateCommon
{
    protected function setUp()
    {
        parent::setUp();
        static::disableCache();
    }

    public function testGetAsArray()
    {
        $params = [
            'array' => $this->getAll(),
        ];
        // null tpl
        $this->assertSame($this->template->getSnippet(ListView::className(), $params), json_encode($params['array']));

        // tpl + wrapper tpl
        $params['tpl'] = "@INLINE<h1>[[+name]]</h1>\n<p>[[+email]]</p>\n[[!+about]]\n[[+currentItem]]";
        $params['wrapperTpl'] = "@INLINE[[!+output]]\n[[+countItems]]";
        $this->assertSame($this->removeSpace($this->template->getSnippet(ListView::className(), $params)), $this->removeSpace(file_get_contents($this->path . '/snippet_as_array.html')));

        // pagination
        $params['pagination']['array'] = Pagination::get(count($params['array']), 1, 1, SORT_DESC);
        $params['pagination']['pageVar'] = 'num';
        $params['pagination']['toPlaceholder'] = 'pagination';
        $this->assertSame($this->removeSpace($this->template->getSnippet(ListView::className(), $params)), $this->removeSpace(file_get_contents($this->path . '/snippet_as_array.html')));
        $this->assertNotEmpty($this->template->getPlaceholder('pagination', false, true));
    }

    public function testGetAsSingleArray()
    {
        $params['array'] = ['foo', 'bar'];
        $params['tpl'] = "@INLINE<li>[[!+output]][[+currentItem]]</li>";
        $params['wrapperTpl'] = "@INLINE<ul>[[!+output]]</ul>";
        $this->assertSame($this->removeSpace($this->template->getSnippet(ListView::className(), $params)), '<ul><li>foo1</li><li>bar2</li></ul>');
    }

    public function testGetAsMethod()
    {
        $class = ListView::className();
        // null tpl
        $this->assertSame(
            trim($this->template->replace('
                [['.$class.'?call=`'.__CLASS__.'.getAll`]]
            ')),
            json_encode($this->getAll())
        );

        // array is empty
        $this->assertSame(
            trim($this->template->replace('
                [[ListView?array=`[]`]]
            ')),
            ''
        );

        // array is empty  + custom error message
        $this->assertSame(
            trim($this->template->replace('
                [[ListView?array=`[]`?errorText=`empty`]]
            ')),
            'empty'
        );

        // tpl + wrapper tpl
        $this->assertSame(
            $this->removeSpace($this->template->replace('
                [[ListView
                    ?call=`'.__CLASS__.'.getAll`
                    ?tpl=`'. $this->path . '/item`
                    ?wrapperTpl=`'. $this->path . '/wrapper`
                ]]
            ')),
            $this->removeSpace(file_get_contents($this->path . '/snippet_as_array.html'))
        );

        // pagination
        $this->assertSame(
            $this->removeSpace($this->template->replace('
                [[ListView
                    ?call=`'.__CLASS__.'.getAll`
                    ?tpl=`'. $this->path . '/item`
                    ?wrapperTpl=`'. $this->path . '/wrapper`
                    ?pagination=`{"call" : "'.addslashes(__CLASS__).'.getPagination", "toPlaceholder" : "pagination"}`
                ]]
            ')),
            $this->removeSpace(file_get_contents($this->path . '/snippet_as_array.html'))
        );

        $this->assertNotEmpty($this->template->getPlaceholder('pagination', false, true));
    }


    public function testRender()
    {
        $this->template = new Template();
        $this->assertSame(
            $this->removeSpace($this->template->render('@rockunit.tpl/layout', [], new \rockunit\snippets\data\FooController)),
            $this->removeSpace(file_get_contents($this->path . '/_layout.html'))
        );
    }

    public function testCache()
    {
        $cache = static::getCache();
        $this->template = new Template();
        $this->template->cache = $cache;

        $this->assertSame(
            $this->removeSpace($this->template->replace('
                [[ListView
                    ?call=`'.__CLASS__.'.getAll`
                    ?tpl=`'. $this->path . '/item`
                    ?wrapperTpl=`'. $this->path . '/wrapper`
                    ?pagination=`{"call" : "'.addslashes(__CLASS__).'.getPagination", "toPlaceholder" : "pagination"}`
                    ?cacheKey=`list`
                ]]
            ')),
            $this->removeSpace(file_get_contents($this->path . '/snippet_as_array.html'))
        );
        $this->assertTrue($cache->exists('list'));

        // cache toPlaceholder
        $this->template->removeAllPlaceholders(true);
        $this->assertSame(
            $this->removeSpace($this->template->replace('
                [[ListView
                    ?call=`'.__CLASS__.'.getAll`
                    ?tpl=`'. $this->path . '/item`
                    ?wrapperTpl=`'. $this->path . '/wrapper`
                    ?pagination=`{"call" : "'.addslashes(__CLASS__).'.getPagination", "toPlaceholder" : "pagination"}`
                    ?cacheKey=`list`
                ]]
            ')),
            $this->removeSpace(file_get_contents($this->path . '/snippet_as_array.html'))
        );
        $this->assertTrue($cache->exists('list'));
        $this->assertNotEmpty($this->template->getPlaceholder('pagination', false, true));
    }

    public function testCacheExpire()
    {
        if (!interface_exists('\rock\cache\CacheInterface') || !class_exists('\League\Flysystem\Filesystem')) {
            $this->markTestSkipped('Rock cache not installed.');
            return;
        }

        static::clearRuntime();
        $cache = static::getCache();
        $this->template->cache = $cache;
        $this->assertSame(
            $this->removeSpace($this->template->replace('
                [[ListView
                    ?call=`'.__CLASS__.'.getAll`
                    ?tpl=`'. $this->path . '/item`
                    ?wrapperTpl=`'. $this->path . '/wrapper`
                    ?pagination=`{"call" : "'.addslashes(__CLASS__).'.getPagination", "toPlaceholder" : "pagination"}`
                    ?cacheKey=`list`
                    ?cacheExpire=`1`
                ]]
            ')),
            $this->removeSpace(file_get_contents($this->path . '/snippet_as_array.html'))
        );
        $this->assertTrue($cache->exists('list'));
        sleep(4);
        $this->assertFalse($cache->exists('list'));
    }

    public static function getAll()
    {
        return [
            [
                'name' => 'Tom',
                'email' => 'tom@site.com',
                'about' => '<b>biography</b>'
            ],
            [
                'name' => 'Chuck',
                'email' => 'chuck@site.com'
            ]
        ];
    }

    public static function getPagination()
    {
        return Pagination::get(count(static::getAll()), 1, 1, SORT_DESC);
    }



    protected function calculatePath()
    {
        $this->path = __DIR__ . '/data';
    }

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        static::clearRuntime();
        Rock::$app->language = 'en';
    }


    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();
        static::clearRuntime();
    }
}
 