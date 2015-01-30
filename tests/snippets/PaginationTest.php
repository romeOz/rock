<?php

namespace rockunit\snippets;


use rock\Rock;
use rock\snippets\Pagination;
use rock\template\TemplateException;
use rock\template\Template;
use rockunit\core\template\TemplateCommon;

class PaginationTest extends TemplateCommon
{
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        Rock::$app->language = 'en';
    }

    protected function calculatePath()
    {
        $this->path = __DIR__ . '/data';
    }

    public function testGet()
    {
        // null or []
        $template = new Template();
        $template->snippets = [
            'Pagination' => [
                'class' => Pagination::className(),
            ]
        ];

        $this->assertSame($template->getSnippet('Pagination'), null);

        $params = [
          'call' => function(){
                  return \rock\helpers\Pagination::get(0, null, 10, SORT_DESC);
              }
        ];
        $this->assertEmpty($this->template->getSnippet(Pagination::className(), $params));

        // with args + anchor
        $params = [
            'array' => \rock\helpers\Pagination::get(7, null, 5, SORT_DESC),
            'pageArgs' => 'view=all&sort=desc',
            'pageAnchor' => 'name'

        ];

        $this->assertSame(
            static::removeSpace($this->template->getSnippet(Pagination::className(), $params)),
            static::removeSpace(file_get_contents(__DIR__ . '/data/_pagination_args.html'))
        );

        // not args
        $params = [
            'array' => \rock\helpers\Pagination::get(7, null, 5, SORT_DESC),
        ];
        $this->assertSame(
            static::removeSpace($this->template->getSnippet(Pagination::className(), $params)),
            static::removeSpace(file_get_contents(__DIR__ . '/data/_pagination_not_args.html'))
        );
    }

    public function unknownCallException()
    {
        $params = [
            'call' => 'Foo.method'
        ];
        $this->setExpectedException(TemplateException::className());
        $this->template->getSnippet(Pagination::className(), $params);
    }
}
 