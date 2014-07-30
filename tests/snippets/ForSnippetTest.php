<?php

namespace rockunit\snippets;



use rockunit\core\template\TemplateCommon;

class ForSnippetTest extends TemplateCommon
{
    protected function calculatePath()
    {
        $this->path = __DIR__ . '/data';
    }


    public function testGet()
    {
        $this->assertSame(
            $this->template->replace('[[!For?count=`2`
                                            ?tpl=`@INLINE<b>[[+title]]</b>`
                                            ?addPlaceholders=`["title"]`
                                            ?wrapperTpl=`@INLINE<p>[[!+output]]</p>`
                                      ]]',
                                     ['title'=> 'hello world']
            ),
            '<p><b>hello world</b><b>hello world</b></p>'
        );
    }
}
 