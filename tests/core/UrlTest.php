<?php

namespace rockunit\core;


use rock\Rock;
use rock\url\Url;

/**
 * @group base
 */
class UrlTest extends \PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'] = 'site.com';
    }

    protected function tearDown()
    {
        parent::tearDown();
        static::setUpBeforeClass();
    }

    public function testGetCurrentUrl()
    {
        // relative
        $url = new Url();
        $this->assertSame('/', $url->getRelativeUrl());

        // http
        $url = new Url();
        $this->assertSame('http://site.com/', $url->getHttpUrl());

        // https
        $url = new Url();
        $this->assertSame('https://site.com/', $url->getHttpsUrl());

        // absolute
        $_SERVER['HTTP_HOST'] = null;
        $url = new Url();
        $this->assertSame('http://site.com/', $url->getAbsoluteUrl());

        // removing args
        $url = new Url();
        $this->assertSame('http://site.com/', $url->removeArgs(['page'])->getAbsoluteUrl());
        $_SERVER['REQUEST_URI'] = '/?page=1&view=all';
        $url = new Url();
        $this->assertSame('http://site.com/?view=all', $url->removeArgs(['page'])->getAbsoluteUrl());

        // removing all args
        $_SERVER['REQUEST_URI'] = '/?page=1&view=all';
        $url = new Url();
        $this->assertSame('http://site.com/', $url->removeAllArgs()->getAbsoluteUrl());
        $_SERVER['REQUEST_URI'] = '/';

        // adding anchor
        $url = new Url();
        $this->assertSame('http://site.com/#name', $url->addAnchor('name')->getAbsoluteUrl());

        // removing anchor
        $url = new Url();
        $this->assertSame('http://site.com/', $url->removeAnchor()->getAbsoluteUrl());

        // adding end path
        $url = new Url();
        $this->assertSame('http://site.com/news/', $url->addEndPath('news/')->getAbsoluteUrl());

        // replacing URL
        $url = new Url();
        $this->assertSame('http://site.com/', $url->replacePath('news/', '')->getAbsoluteUrl());

        // callback
        $url = new Url();
        $this->assertSame('http://site.com/#foo', $url->callback(function(Url $url){$url->fragment = 'foo';})->getAbsoluteUrl());

        // get host
        $url = new Url();
        $this->assertSame('site.com',$url->host);

        // get host
        $url = new Url();
        $url->user = 'tom';
        $url->pass = '123';
        $this->assertSame('http://tom:123@site.com/', $url->getAbsoluteUrl());

        // build
        $url = new Url();
        $this->assertSame(
            'https://site.com/parts/news/?page=1#name',
            $url->setArgs(['page' => 1])
                ->addBeginPath('/parts')
                ->addEndPath('/news/')
                ->addAnchor('name')
                ->getHttpsUrl()
        );

        // build + strip_tags
        $url = new Url();
        $this->assertSame(
            '/parts/news/?page=1&view=all#name',
            $url
                ->addBeginPath('/parts')
                ->addEndPath('/<b>news</b>/')
                ->addAnchor('name')
                ->removeAllArgs()
                ->setArgs(['page' => 1])
                ->addArgs(['view'=> 'all'])
                ->getRelativeUrl()
        );

        // build + remove args
        $url = new Url();
        $this->assertSame(
            '/parts/news/#name',
            $url
                ->setArgs(['page' => 1])
                ->addBeginPath('/parts')
                ->addEndPath('/news/')
                ->addAnchor('name')
                ->removeAllArgs()
                ->getRelativeUrl()
        );

        // build + add args
        $url = new Url();
        $this->assertSame(
            '/parts/news/?view=all#name',
            $url
                ->addBeginPath('/parts')
                ->addEndPath('/news/')
                ->addAnchor('name')
                ->removeAllArgs()
                ->addArgs(['view'=> 'all'])
                ->getRelativeUrl()
        );

        // get unknown data of url
        $this->assertNull((new Url())->foo);
    }

    public function testGetCustomUrl()
    {
        // relative
        $url = new Url('http://site.com/?page=2#name');
        $this->assertSame('/?page=2#name',$url->getRelativeUrl());

        // https
        $url = Rock::factory('http://site.com/?page=2#name', 'url');
        $this->assertSame('https://site.com/?page=2#name', $url->getHttpsUrl());

        // http
        $url = Rock::factory('https://site.com/?page=2#name', Url::className());
        $this->assertSame('http://site.com/?page=2#name', $url->getHttpUrl());

        // removing anchor
        $url = new Url('https://site.com/?page=2#name');
        $this->assertSame('https://site.com/?page=2', $url->removeAnchor()->getAbsoluteUrl());

        // replacing URL
        $url =  new Url('http://site.com/news/?page=2#name');
        $this->assertSame('http://site.com/?page=2#name', $url->replacePath('news/', '')->getAbsoluteUrl());

        // build + add args + self host
        $url = new Url('http://site2.com/?page=2#name');
        $this->assertSame(
            'http://site.com/parts/news/?page=2&view=all#name',
            $url
                ->addBeginPath('/parts')
                ->addEndPath('/news/')
                ->addAnchor('name')
                ->addArgs(['view'=> 'all'])
                ->getAbsoluteUrl(true)
        );

        // build + remove args
        $url = new Url('http://site2.com/?page=2#name');
        $this->assertSame(
            'http://site2.com/parts/news/?view=all#name',
            $url
                ->addBeginPath('/parts')
                ->addEndPath('/news/')
                ->addArgs(['view'=> 'all'])
                ->removeArgs(['page'])
                ->getAbsoluteUrl()
        );
    }
}