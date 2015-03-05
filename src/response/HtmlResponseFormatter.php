<?php

namespace rock\response;


use rock\base\ObjectInterface;
use rock\base\ObjectTrait;

class HtmlResponseFormatter implements ResponseFormatterInterface, ObjectInterface
{
    use ObjectTrait;

    /**
     * @var string the Content-Type header for the response
     */
    public $contentType = 'text/html';

    /**
     * Formats the specified response.
     * @param Response $response the response to be formatted.
     */
    public function format($response)
    {
        if (stripos($this->contentType, 'charset') === false) {
            $this->contentType .= '; charset=' . $response->charset;
        }
        $response->getHeaders()->set('Content-Type', $this->contentType);
        $response->content = $response->data;
    }

} 