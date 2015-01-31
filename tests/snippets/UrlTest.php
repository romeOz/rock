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
            'http://site.com/parts/categories/news/?view=all&page=1#name',
            $this->template->replace('[[Url
                        ?url=`http://site.com/categories/?view=all`
                        ?addArgs=`{"page" : 1}`
                        ?beginPath=`/parts`
                        ?endPath=`/news/`
                        ?anchor=`name`
                        ?const=`32`
                    ]]'
            )
        );

        // replacing URL
        $this->assertSame(
            'http://site.com/?view=all',
            $this->template->replace('[[Url
                        ?url=`http://site.com/news/?view=all`
                        ?replace=`["news/", ""]`
                        ?const=`32`
                    ]]'
            )
        );

        // modify url + remove args + add args
        $this->assertSame(
            'http://site.com/categories/?page=1',
            $this->template->getSnippet(
                'Url',
                [
                    'url' => 'http://site.com/categories/?view=all',
                    'removeArgs' => ['view'],
                    'args' => ['page' => 1],
                    'const' => Url::ABS
                ]
            )
        );

        // modify url + remove all args
        $template = new Template();
        $this->assertSame(
            'http://site.com/categories/',
            $template->getSnippet(
                'Url',
                [
                    'url' => 'http://site.com/categories/?view=all#name',
                    'removeAllArgs' => true,
                    'removeAnchor' => true,
                    'const' => Url::ABS
                ]
            )
        );

        // modify self url + input null
        $this->assertSame(
            'http://site.com/',
            $this->template->getSnippet(
                'Url',
                [
                    'removeAllArgs' => true,
                    'const' => Url::ABS
                ]
            )
        );
    }
}