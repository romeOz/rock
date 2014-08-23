<?php

namespace rockunit\snippets;


use rock\snippets\Url;
use rock\template\Template;
use rockunit\core\template\TemplateCommon;

class UrlTest extends TemplateCommon
{
    protected function calculatePath()
    {
        $this->path = __DIR__ . '/data';
    }

    public function testGet()
    {
        $this->assertSame(
            $this->template->replace('[[Url
                        ?url=`http://site.com/categories/?view=all`
                        ?addArgs=`{"page" : 1}`
                        ?beginPath=`/parts`
                        ?endPath=`/news/`
                        ?anchor=`name`
                        ?const=`32`
                    ]]'
            ),
            'http://site.com/parts/categories/news/?view=all&page=1#name'
        );

        // modify url + remove args + add args
        $this->assertSame(
            $this->template->getSnippet(
                Url::className(),
                [
                    'url' => 'http://site.com/categories/?view=all',
                    'removeArgs' => ['view'],
                    'args' => ['page' => 1],
                    'const' => Url::ABS
                ]
            ),
            'http://site.com/categories/?page=1'
        );

        // modify url + remove all args
        $template = new Template();
        $this->assertSame(
            $template->getSnippet(
                'Url',
                [
                    'url' => 'http://site.com/categories/?view=all#name',
                    'removeAllArgs' => true,
                    'removeAnchor' => true,
                    'const' => Url::ABS
                ]
            ),
            'http://site.com/categories/'
        );

        // modify url + input null
        $this->assertSame(
            $this->template->getSnippet(
                Url::className(),
                [
                    'removeAllArgs' => true,
                    'const' => Url::ABS
                ]
            ),
            'http://site.com/'
        );
    }
}
 