<?php

namespace rock\response;


use FeedWriter\ATOM;
use FeedWriter\Feed;
use FeedWriter\RSS1;
use FeedWriter\RSS2;
use rock\base\ObjectInterface;
use rock\base\ObjectTrait;

class RssResponseFormatter implements ResponseFormatterInterface, ObjectInterface
{
    use ObjectTrait {
        ObjectTrait::__construct as parentConstruct;
    }

    const RSS1 = 1;
    const RSS2 = 2;
    const ATOM = 3;
    /**
     * @var string the Content-Type header for the response
     */
    public $contentType = 'application/rss+xml';
    /**
     * @var string the XML version
     */
    public $version = '1.0';
    /**
     * @var string the XML encoding.
     * If not set, it will use the value of {@see \rock\response\Response::$charset}.
     */
    public $encoding;

    public $type = self::RSS2;

    /** @var  Feed */
    private $_feedWriter;

    public function __construct(array $configs = [])
    {
        $this->parentConstruct($configs);

        if (!isset($this->_feedWriter)) {
            switch ($this->type) {
                case 1:
                    $this->_feedWriter = new RSS1();
                    $this->contentType = 'application/rdf+xml';
                    return;
                case 2:
                    $this->_feedWriter = new RSS2();
                    $this->contentType = 'application/rss+xml';
                    return;
                case 3:
                    $this->_feedWriter = new ATOM();
                    $this->contentType = 'application/atom+xml';
                    return;
            }
        }
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

        $data = $response->data;

        if (!isset($data['language'])) {
            $this->_feedWriter->setChannelElement('language', $response->locale);
        }
        if (!isset($data['pubDate'])) {
            $this->_feedWriter->setChannelElement('pubDate', date(DATE_RSS, time()));
        }
        if (isset($data['title'])) {
            $this->_feedWriter->setTitle($data['title']);
        }
        if (isset($data['link'])) {
            $this->_feedWriter->setLink($data['link']);
        }
        if (isset($data['description'])) {
            $this->_feedWriter->setDescription($data['description']);
        }

        if (isset($data['items']) && is_array($data['items'])) {
            if ($data['items']) {
                foreach ($data['items'] as $item) {
                    $newItem = $this->_feedWriter->createNewItem();
                    $newItem->addElementArray($item);
                    $this->_feedWriter->addItem($newItem);
                }
            }
        }
        $response->content = $this->_feedWriter->generateFeed();
    }
}