<?php

namespace rockunit\core\helpers;



use rock\helpers\Pagination;

class PaginationTest extends \PHPUnit_Framework_TestCase
{
    public function testGetAsSortASC()
    {
        // count "0"
        $this->assertSame(Pagination::get(0), []);

        $actual = array (
            'pageVar' => 'page',
            'pageCount' => 1,
            'pageCurrent' => 1,
            'pageStart' => 1,
            'pageEnd' => 1,
            'pageDisplay' =>
                array (
                    0 => 1,
                ),
            'pageFirst' => NULL,
            'pageLast' => NULL,
            'offset' => 0,
            'limit' => 10,
            'countMore' => 0,
        );
        $this->assertSame(Pagination::get(7,1),$actual);

        $actual = array (
            'pageVar' => 'page',
            'pageCount' => 5,
            'pageCurrent' => 2,
            'pageStart' => 1,
            'pageEnd' => 5,
            'pageDisplay' =>
                array (
                    0 => 1,
                    1 => 2,
                    2 => 3,
                    3 => 4,
                    4 => 5,
                ),
            'pagePrev' => 1,
            'pageNext' => 3,
            'pageFirst' => 1,
            'pageLast' => 5,
            'offset' => 10,
            'limit' => 10,
            'countMore' => 30,
        );
        $this->assertSame(Pagination::get(50, 2), $actual);

        // first page
        $actual = array (
            'pageVar' => 'page',
            'pageCount' => 2,
            'pageCurrent' => 1,
            'pageStart' => 1,
            'pageEnd' => 2,
            'pageDisplay' =>
                array (
                    0 => 1,
                    1 => 2,
                ),
            'pageNext' => 2,
            'pageFirst' => NULL,
            'pageLast' => 2,
            'offset' => 0,
            'limit' => 5,
            'countMore' => 2,
        );
        $this->assertSame(Pagination::get(7,null,5), $actual);
        $this->assertSame(Pagination::get(7,0,5), $actual);
        $this->assertSame(Pagination::get(7,-1,5), $actual);
        $this->assertSame(Pagination::get(7,'foo',5), $actual);

        // next page
        $actual = array (
            'pageVar' => 'page',
            'pageCount' => 2,
            'pageCurrent' => 1,
            'pageStart' => 1,
            'pageEnd' => 2,
            'pageDisplay' =>
                array (
                    0 => 1,
                    1 => 2,
                ),
            'pageNext' => 2,
            'pageFirst' => NULL,
            'pageLast' => 2,
            'offset' => 0,
            'limit' => 5,
            'countMore' => 2,
        );
        $this->assertSame(Pagination::get(7, 1, 5), $actual);

        // page last
        $actual = array (
            'pageVar' => 'page',
            'pageCount' => 2,
            'pageCurrent' => 2,
            'pageStart' => 1,
            'pageEnd' => 2,
            'pageDisplay' =>
                array (
                    0 => 1,
                    1 => 2,
                ),
            'pagePrev' => 1,
            'pageFirst' => 1,
            'pageLast' => NULL,
            'offset' => 5,
            'limit' => 5,
            'countMore' => 0,
        );
        $this->assertSame(Pagination::get(7, 7, 5), $actual);

    }

    public function testGetAsSortDESC()
    {
        // first page
        $actual = array (
            'pageVar' => 'page',
            'pageCount' => 2,
            'pageCurrent' => 2,
            'pageStart' => 2,
            'pageEnd' => 1,
            'pageDisplay' =>
                array (
                    0 => 2,
                    1 => 1,
                ),
            'pageNext' => 1,
            'pageFirst' => NULL,
            'pageLast' => 1,
            'offset' => 0,
            'limit' => 5,
            'countMore' => 2,
        );
        $this->assertSame(Pagination::get(7, null, 5, SORT_DESC), $actual);
        $this->assertSame(Pagination::get(7, 0, 5, SORT_DESC), $actual);
        $this->assertSame(Pagination::get(7, -1, 5, SORT_DESC), $actual);
        $this->assertSame(Pagination::get(7, 'foo', 5, SORT_DESC), $actual);

        $actual = array (
            'pageVar' => 'page',
            'pageCount' => 5,
            'pageCurrent' => 2,
            'pageStart' => 5,
            'pageEnd' => 1,
            'pageDisplay' =>
                array (
                    0 => 5,
                    1 => 4,
                    2 => 3,
                    3 => 2,
                    4 => 1,
                ),
            'pagePrev' => 3,
            'pageNext' => 1,
            'pageFirst' => 5,
            'pageLast' => 1,
            'offset' => 30,
            'limit' => 10,
            'countMore' => 10,
        );
        $this->assertSame(Pagination::get(50, 2, 10, SORT_DESC), $actual);

        // next page
        $actual = array (
            'pageVar' => 'page',
            'pageCount' => 2,
            'pageCurrent' => 2,
            'pageStart' => 2,
            'pageEnd' => 1,
            'pageDisplay' =>
                array (
                    0 => 2,
                    1 => 1,
                ),
            'pageNext' => 1,
            'pageFirst' => NULL,
            'pageLast' => 1,
            'offset' => 0,
            'limit' => 5,
            'countMore' => 2,
        );
        $this->assertSame(Pagination::get(7, 6, 5, SORT_DESC), $actual);

        // last page
        $actual = array (
            'pageVar' => 'page',
            'pageCount' => 2,
            'pageCurrent' => 1,
            'pageStart' => 2,
            'pageEnd' => 1,
            'pageDisplay' =>
                array (
                    0 => 2,
                    1 => 1,
                ),
            'pagePrev' => 2,
            'pageFirst' => 2,
            'pageLast' => NULL,
            'offset' => 5,
            'limit' => 5,
            'countMore' => 0,
        );
        $this->assertSame(Pagination::get(7, 1, 5, SORT_DESC), $actual);
    }
}