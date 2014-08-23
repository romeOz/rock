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
        $this->assertSame($url->getRelativeUrl(),'/');

        // http
        $url = new Url();
        $this->assertSame($url->getHttpUrl(),'http://site.com/');

        // https
        $url = new Url();
        $this->assertSame($url->getHttpsUrl(),'https://site.com/');

        // absolute
        $_SERVER['HTTP_HOST'] = null;
        $url = new Url();
        $this->assertSame($url->getAbsoluteUrl(),'http://site.com/');

        // remove args
        $url = new Url();
        $this->assertSame($url->removeArgs(['page'])->getAbsoluteUrl(),'http://site.com/');
        $_SERVER['REQUEST_URI'] = '/?page=1&view=all';
        $url = new Url();
        $this->assertSame($url->removeArgs(['page'])->getAbsoluteUrl(),'http://site.com/?view=all');

        // remove all args
        $_SERVER['REQUEST_URI'] = '/?page=1&view=all';
        $url = new Url();
        $this->assertSame($url->removeAllArgs()->getAbsoluteUrl(),'http://site.com/');
        $_SERVER['REQUEST_URI'] = '/';

        // add anchor
        $url = new Url();
        $this->assertSame($url->addAnchor('name')->getAbsoluteUrl(),'http://site.com/#name');

        // remove anchor
        $url = new Url();
        $this->assertSame($url->removeAnchor()->getAbsoluteUrl(),'http://site.com/');

        // add end path
        $url = new Url();
        $this->assertSame($url->addEndPath('news/')->getAbsoluteUrl(),'http://site.com/news/');

        // callback
        $url = new Url();
        $this->assertSame($url->callback(function(Url $url){$url->fragment = 'foo';})->getAbsoluteUrl(),'http://site.com/#foo');

        // get host
        $url = new Url();
        $this->assertSame($url->host,'site.com');

        // get host
        $url = new Url();
        $url->user = 'tom';
        $url->pass = '123';
        $this->assertSame($url->getAbsoluteUrl(),'http://tom:123@site.com/');

        // build
        $url = new Url();
        $this->assertSame(
            $url->setArgs(['page' => 1])
                ->addBeginPath('/parts')
                ->addEndPath('/news/')
                ->addAnchor('name')
                ->getHttpsUrl(),
            'https://site.com/parts/news/?page=1#name'
        );

        // build + strip_tags
        $url = new Url();
        $this->assertSame(
            $url
                ->addBeginPath('/parts')
                ->addEndPath('/<b>news</b>/')
                ->addAnchor('name')
                ->removeAllArgs()
                ->setArgs(['page' => 1])
                ->addArgs(['view'=> 'all'])
                ->getRelativeUrl(),
            '/parts/news/?page=1&view=all#name'
        );

        // build + remove args
        $url = new Url();
        $this->assertSame(
            $url
                ->setArgs(['page' => 1])
                ->addBeginPath('/parts')
                ->addEndPath('/news/')
                ->addAnchor('name')
                ->removeAllArgs()
                ->getRelativeUrl(),
            '/parts/news/#name'
        );

        // build + add args
        $url = new Url();
        $this->assertSame(
            $url
                ->addBeginPath('/parts')
                ->addEndPath('/news/')
                ->addAnchor('name')
                ->removeAllArgs()
                ->addArgs(['view'=> 'all'])
                ->getRelativeUrl(),
            '/parts/news/?view=all#name'
        );

        // get unknown data of url
        $this->assertNull((new Url())->foo);
    }

    public function testGetCustomUrl()
    {
        // relative
        $url = new Url('http://site.com/?page=2#name');
        $this->assertSame($url->getRelativeUrl(),'/?page=2#name');

        // https
        $url = Rock::factory('http://site.com/?page=2#name', 'url');
        $this->assertSame($url->getHttpsUrl(),'https://site.com/?page=2#name');

        // http
        $url = Rock::factory('https://site.com/?page=2#name', Url::className());
        $this->assertSame($url->getHttpUrl(),'http://site.com/?page=2#name');

        // remove anchor
        $url = new Url('https://site.com/?page=2#name');
        $this->assertSame($url->removeAnchor()->getAbsoluteUrl(),'https://site.com/?page=2');

        // build + add args + self host
        $url = new Url('http://site2.com/?page=2#name');
        $this->assertSame(
            $url
                ->addBeginPath('/parts')
                ->addEndPath('/news/')
                ->addAnchor('name')
                ->addArgs(['view'=> 'all'])
                ->getAbsoluteUrl(true),
            'http://site.com/parts/news/?page=2&view=all#name'
        );

        // build + remove args
        $url = new Url('http://site2.com/?page=2#name');
        $this->assertSame(
            $url
                ->addBeginPath('/parts')
                ->addEndPath('/news/')
                ->addArgs(['view'=> 'all'])
                ->removeArgs(['page'])
                ->getAbsoluteUrl(),
            'http://site2.com/parts/news/?view=all#name'
        );
    }
}
 