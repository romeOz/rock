<?php

namespace rock\response;


use rock\base\ObjectInterface;
use rock\base\ObjectTrait;
use rock\helpers\Helper;
use Tackk\Cartographer\Sitemap;

class SitemapResponseFormatter implements ResponseFormatterInterface, ObjectInterface
{
    use ObjectTrait {
        ObjectTrait::__construct as parentConstruct;
    }

    const ALWAYS  = 'always';
    const HOURLY  = 'hourly';
    const DAILY   = 'daily';
    const WEEKLY  = 'weekly';
    const MONTHLY = 'monthly';
    const YEARLY  = 'yearly';
    const NEVER   = 'never';

    /**
     * @var string the Content-Type header for the response
     */
    public $contentType = 'application/xml';
    /**
     * @var string the XML version
     */
    public $version = '1.0';
    /**
     * @var string the XML encoding.
     * If not set, it will use the value of {@see \rock\response\Response::$charset}.
     */
    public $encoding;


    /** @var  Sitemap */
    private $_sitemap;

    public function __construct(array $configs = [])
    {
        $this->parentConstruct($configs);
        $this->_sitemap = new Sitemap();
    }


    /**
     * Formats the specified response.
     * @param Response $response the response to be formatted.
     */
    public function format($response)
    {
        $charset = $this->encoding === null ? $response->charset : $this->encoding;
        if (stripos($this->contentType, 'charset') === false) {
            $this->contentType .= '; charset=' . $charset;
        }
        $response->getHeaders()->set('Content-Type', $this->contentType);

        foreach ($response->data as $value) {
            $this->_sitemap->add(
                $value['loc'],
                Helper::getValue($value['lastmod']),
                Helper::getValue($value['changefreq']),
                isset($value['priority']) ? $value['priority'] : null
            );
        }

        $response->content = $this->_sitemap->toString();
    }
} 