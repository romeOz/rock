<?php

namespace rockunit\snippets;

use rock\snippets\Formula;
use rockunit\core\template\TemplateCommon;


class FormulaTest extends TemplateCommon
{
    protected function calculatePath()
    {
        $this->path = __DIR__ . '/data';
    }

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        static::clearRuntime();
    }


    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();
        static::clearRuntime();
    }

    public function testGet()
    {
        $this->assertSame(
            $this->template->replace('[[Formula
                        ?subject=`:num - 1`
                        ?operands=`{"num" : "[[+num]]"}`
                    ]]',
                    ['num'=> 8]
            ),
            '7'
        );

        // null
        $this->assertSame(
            $this->template->replace('[[Formula]]'),
            ''
        );

        $this->assertSame($this->template->getSnippet('Formula', ['subject' => ':num - 1', 'operands' => ['num' => 8]]), 7);

        // string
        $this->assertSame($this->template->getSnippet('Formula', ['subject' => ':num - 1', 'operands' => ['num' => 'foo']]), -1);
    }
}
 