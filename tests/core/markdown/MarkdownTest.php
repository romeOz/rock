<?php

namespace rockunit\core\markdown;


use League\Flysystem\Adapter\Local;
use rock\file\FileManager;
use rock\image\DataProvider;
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
        $result = Rock::$app->markdown->parseParagraph('![:youtube {.class1 #id1 .class2} 480x360](6JvDSwFtEC0 "title")');
        $this->assertSame(
            $result,
            '<iframe src="//youtube.com/embed/6JvDSwFtEC0/"  frameborder="0" allowfullscreen="allowfullscreen" width="480" height="360" title="title"  class="class1 class2" id="id1"></iframe>'
        );
    }

    public function testVideoSuccess()
    {
        $markdown = Rock::$app->markdown;
        $result = $markdown->parse('![:youtube 480x360 {.class1 #id1 .class2}][video]
Test

[video]: 6JvDSwFtEC0');
        $this->assertSame(
            $result,
            '<p><iframe src="//youtube.com/embed/6JvDSwFtEC0/"  frameborder="0" allowfullscreen="allowfullscreen" width="480" height="360"  class="class1 class2" id="id1"></iframe>
Test</p>'
        );
    }

    public function testVideoFail()
    {
        $markdown = Rock::$app->markdown;
        $markdown->denyTags = ['video'];
        $result = $markdown->parse('![:youtube 480x360 {.class1 #id1 .class2}](6JvDSwFtEC0)');
        $this->assertSame(
            $result,
            '<p><img src="6JvDSwFtEC0" alt="" class="class1 class2" id="id1" /></p>'
        );
    }

    public function testVideoDummy()
    {
        $markdown = Rock::$app->markdown;
        $markdown->enabledDummy = true;
        $markdown->imgDummy = '/src/play.png';
        $markdown->specialAttributesDummy = '.dummy-video';
        //$markdown->denyTags = ['code'];
        $result = $markdown->parse('![:youtube 480x360 {.class1 #id1 .class2}][video]
Test

[video]: 6JvDSwFtEC0');
        $this->assertSame(
            $result,
            '<p><a href="https://www.youtube.com/watch?v=6JvDSwFtEC0" style="width: 480px; height: 360px" target="_blank" rel="nofollow"  class="class1 class2 dummy-video" id="id1"><img src="/src/play.png" /></a>
Test</p>'
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
            $result,
        '<table class="class1 class1" id="id1">
<thead>
<tr><th align="left">header_1 </th><th align="left">header_2 </th><th align="center">header_3</th></tr>
</thead>
<tbody>
<tr><td align="left"><strong>Foo</strong> </td><td align="left">bar </td><td align="center">123</td></tr>
</tbody>
</table>'
        );
    }


    public function testLinkInline()
    {
        $result = Rock::$app->markdown->parseParagraph('[{.class1 #id1 .class2} text](http://test/ "title text")');
        $this->assertSame(
            $result,
            '<a href="http://test/" title="title text" rel="nofollow"  class="class1 class2" id="id1">text</a>'
        );
    }

    public function testLink()
    {
        $result = Rock::$app->markdown->parse('[{.class1 #id1 .class2} text][link]
Test

[link]: http://test/');
        $this->assertSame(
            $result,
            '<p><a href="http://test/" rel="nofollow"  class="class1 class2" id="id1">text</a>
Test</p>'
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
            $mark->parse('![:thumb 50x50{.class2 #id2 .class}](/src/play.png)'),
            '<p><img src="/src/cache/50x50/play.png" alt="" class="class2 class" id="id2" /></p>'
        );

        $this->assertSame(
            $mark->parse('![:thumb {.class2 #id2 .class}](/src/play.png)'),
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
            $mark->parse('![:thumb 50x50{.class2 #id2 .class}](/src/foo.png)'),
            '<p><img src="/src/foo.png" alt="" class="class2 class" id="id2" /></p>'
        );

        $mark->denyTags = ['thumb'];
        $this->assertSame(
            $mark->parse('![:thumb 50x50{.class2 #id2 .class}](/src/foo.png)'),
            '<p><img src="/src/foo.png" alt="" class="class2 class" id="id2" /></p>'
        );
    }

    public function testUsernameLinkSuccess()
    {
        ActiveRecord::$db = $this->getConnection();
        $markdown = Rock::$app->markdown;
        $markdown->handlerLinkByUsername = function($username){
            return Users::findUrlByUsername($username);
        };
        $result = $markdown->parse('@Linda');
        $this->assertSame($result, '<p><a href="/linda/" title="Linda">@Linda</a></p>');
    }

    public function testUsernameLinkFail()
    {
        ActiveRecord::$db = $this->getConnection();
        $markdown = Rock::$app->markdown;
        $markdown->handlerLinkByUsername = function($username){
            return Users::findUrlByUsername($username);
        };
        $result = $markdown->parse('@Tom');
        $this->assertSame($result, '<p><a href="#" title="Tom">@Tom</a></p>');
    }

    public function testDenyTags()
    {
        $markdown = Rock::$app->markdown;
        $markdown->denyTags = ['class'];
        $result = $markdown->parse('h1 {.class1 #id1 .class2}
==

text');
        $this->assertSame(
            $result,
            '<h1>h1</h1>
<p>text</p>'
        );
    }


    public function testCodeFail()
    {
        $markdown = Rock::$app->markdown;
        $markdown->denyTags = ['code'];
        $this->assertSame($markdown->parse('     foo'), '');
        $this->assertSame(
            $markdown->parse('
ghgh

```php
            gjh

```

dfdfdf'),
            '<p>ghgh</p>

<p>dfdfdf</p>'

        );
    }
}
 