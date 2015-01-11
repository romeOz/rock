<?php

namespace rockunit\core\markdown;


use League\Flysystem\Adapter\Local;
use rock\file\FileManager;
use rock\image\DataProvider;
use rock\markdown\Markdown;
use rock\Rock;
use rockunit\core\db\DatabaseTestCase;
use rockunit\core\db\models\ActiveRecord;
use rockunit\core\db\models\Users;

/**
 * @group base
 * @group db
 */
class MarkdownTest extends DatabaseTestCase
{
    public function testVideoInline()
    {
        $result = Rock::$app->markdown->parseParagraph('![:youtube 480x360](6JvDSwFtEC0 "title"){.class1 #id1 .class2}');
        $this->assertSame(
            '<iframe src="//youtube.com/embed/6JvDSwFtEC0/" title="title" width="480" height="360" allowfullscreen="allowfullscreen" frameborder="0" class="class1 class2" id="id1"></iframe>',
            $result
        );
    }

    public function testVideoSuccess()
    {
        $markdown = Rock::$app->markdown;
        $result = $markdown->parse('![:youtube 480x360][video]
Test

[video]: 6JvDSwFtEC0 {.class1 #id1 .class2}');
        $this->assertSame(
            '<p><iframe src="//youtube.com/embed/6JvDSwFtEC0/" width="480" height="360" allowfullscreen="allowfullscreen" frameborder="0" class="class1 class2" id="id1"></iframe>
Test</p>',
            $result
        );
    }

    public function testVideoFail()
    {
        $markdown = Rock::$app->markdown;
        $markdown->denyTags = ['video'];
        $result = $markdown->parse('![:youtube 480x360](6JvDSwFtEC0){.class1 #id1 .class2}');
        $this->assertSame(
            $result,
            '<p><img src="6JvDSwFtEC0" alt="" class="class1 class2" id="id1" /></p>'
        );
    }

    public function testVideoDummy()
    {
        $markdown = Rock::$app->markdown;
        $markdown->dummy = Markdown::DUMMY;
        $markdown->specialAttributesDummy = '.dummy-video';
        //$markdown->denyTags = ['code'];
        $result = $markdown->parse('![:youtube 480x360][video]
Test

[video]: 6JvDSwFtEC0 {.class1 #id1 .class2}');
        $this->assertSame(
            '<p><a href="https://www.youtube.com/watch?v=6JvDSwFtEC0" style="width: 480px; height: 360px" target="_blank" rel="nofollow"  class="dummy-video class1 class2" id="id1" ></a>
Test</p>',
            $result
        );
    }

    public function testTable()
    {
        $result = Rock::$app->markdown->parse('
{.class1 #id1 .class1}
| header_1 | header_2 | header_3 |
|:--| :--- | :---: |
| **Foo** | bar | 123 |

');
        $this->assertSame(
        '<table class="class1 class1" id="id1">
<thead>
<tr><th align="left">header_1 </th><th align="left">header_2 </th><th align="center">header_3</th></tr>
</thead>
<tbody>
<tr><td align="left"><strong>Foo</strong> </td><td align="left">bar </td><td align="center">123</td></tr>
</tbody>
</table>',
        $result
        );
    }


    public function testLinkInline()
    {
        $result = Rock::$app->markdown->parseParagraph('[text](http://test/ "title text"){.class1 #id1 .class2}');
        $this->assertSame(
            '<a href="http://test/" title="title text" class="class1 class2" id="id1"  rel="nofollow" target="_blank">text</a>',
            $result
        );
    }

    public function testLink()
    {
        $result = Rock::$app->markdown->parse('[text][link]
Test

[link]: http://test/ {.class1 #id1 .class2}');
        $this->assertSame(
            '<p><a href="http://test/" class="class1 class2" id="id1"  rel="nofollow" target="_blank">text</a>
Test</p>',
            $result
        );
    }

    public function testThumbSuccess()
    {
        $mark = Rock::$app->markdown;
        $dataImage = new DataProvider(
            [
                'srcImage' => '/src',
                'srcCache' => '/src/cache',
            ]
        );

        $dataImage::$adapterImage = Rock::factory(
            [
                'class' => FileManager::className(),
                'adapter' =>
                    function () {
                        return new Local(Rock::getAlias('@tests/core/markdown/src'));
                    },
            ]
        );
        $dataImage::$adapterCache = Rock::factory(
            [
                'class' => FileManager::className(),
                'adapter' =>
                    function () {
                        return new Local(Rock::getAlias('@tests/core/markdown/src/cache'));
                    },
            ]
        );
        $mark->dataImage = $dataImage;
        $this->assertSame(
            '<p><img src="/src/cache/50x50/play.png" alt="" class="class2 class" id="id2" /></p>',
            $mark->parse('![:thumb 50x50](/src/play.png){.class2 #id2 .class}')
        );

        $this->assertSame(
            $mark->parse('![:thumb](/src/play.png){.class2 #id2 .class}'),
            '<p><img src="/src/play.png" alt="" class="class2 class" id="id2" /></p>'
        );
    }
    public function testThumbFail()
    {
        $mark = Rock::$app->markdown;
        $dataImage = new DataProvider(
            [
                'srcImage' => '/src',
                'srcCache' => '/src/cache',
            ]
        );

        $dataImage::$adapterImage = Rock::factory(
            [
                'class' => FileManager::className(),
                'adapter' =>
                    function () {
                        return new Local(Rock::getAlias('@tests/core/markdown/src'));
                    },
            ]
        );
        $dataImage::$adapterCache = Rock::factory(
            [
                'class' => FileManager::className(),
                'adapter' =>
                    function () {
                        return new Local(Rock::getAlias('@tests/core/markdown/src/cache'));
                    },
            ]
        );
        $mark->dataImage = $dataImage;
        $this->assertSame(
            $mark->parse('![:thumb 50x50](/src/foo.png){.class2 #id2 .class}'),
            '<p><img src="/src/foo.png" alt="" class="class2 class" id="id2" /></p>'
        );

        $mark->denyTags = ['thumb'];
        $this->assertSame(
            $mark->parse('![:thumb 50x50](/src/foo.png){.class2 #id2 .class}'),
            '<p><img src="/src/foo.png" alt="" class="class2 class" id="id2" /></p>'
        );
    }

    public function testUsernameLinkSuccess()
    {
        ActiveRecord::$connection = $this->getConnection();
        $markdown = Rock::$app->markdown;
        $markdown->handlerLinkByUsername = function($username){
            return Users::findUrlByUsername($username);
        };
        $result = $markdown->parse('@Linda');
        $this->assertSame('<p><a href="/linda/" title="Linda">@Linda</a></p>', $result);

        $result = $markdown->parse('Hi @Linda, foo');
        $this->assertSame('<p>Hi <a href="/linda/" title="Linda">@Linda</a>, foo</p>', $result);
    }

    public function testUsernameLinkFail()
    {
        ActiveRecord::$connection = $this->getConnection();
        $markdown = Rock::$app->markdown;
        $markdown->handlerLinkByUsername = function($username){
            return Users::findUrlByUsername($username);
        };
        $result = $markdown->parse('@Tom');
        $this->assertSame('<p><a href="#" title="Tom">@Tom</a></p>', $result);
    }

    public function testDenyTags()
    {
        $markdown = Rock::$app->markdown;
        $markdown->denyTags = ['class'];
        $result = $markdown->parse('h1 {.class1 #id1 .class2}
==

text');
        $this->assertSame(
            '<h1>h1</h1>
<p>text</p>',
            $result
        );
    }


    public function testCodeFail()
    {
        $markdown = Rock::$app->markdown;
        $markdown->denyTags = ['code'];
        $this->assertSame($markdown->parse('     foo'), '');
        $this->assertSame(
            '<p>foo</p>
<p>bar</p>',
            $markdown->parse('
foo

```php
            gjh

```

bar')
        );
    }
}