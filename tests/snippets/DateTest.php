<?php

namespace rockunit\snippets;


use rock\snippets\Date;
use rockunit\core\template\TemplateCommon;

class DateTest extends TemplateCommon
{
    protected function calculatePath()
    {
        $this->path = __DIR__ . '/data';
    }

    public function testGet()
    {
        $this->assertSame(
            $this->template->replace('[[Date
                        ?date=`2012-02-12 15:01`
                        ?format=`dmyhm`
                    ]]'
            ),
            '12 February 2012 15:01'
        );

        $this->assertSame(
            $this->template->replace('[[Date
                        ?date=`2012-02-12 15:01`
                        ?format=`j n`
                    ]]'
            )
            ,
            '12 2'
        );

        // default format
        $this->assertSame(
            $this->template->getSnippet('Date', ['date' => '2012-02-12 15:01']),
            '2012-02-12 15:01:00'
        );
    }
}
 