<?php

namespace rock\response;


use rock\base\ObjectInterface;
use rock\base\ObjectTrait;
use rock\helpers\Json;

class JsonResponseFormatter implements ResponseFormatterInterface, ObjectInterface
{
    use ObjectTrait;

    /**
     * @var boolean whether to use JSONP response format. When this is true, the {@see \rock\response\Response::$data}
     * must be an array consisting of `data` and `callback` members. The latter should be a JavaScript
     * function name while the former will be passed to this function as a parameter.
     */
    public $useJsonp = false;

    /**
     * Formats the specified response.
     * @param Response $response the response to be formatted.
     */
    public function format($response)
    {
        if ($this->useJsonp) {
            $this->formatJsonp($response);
        } else {
            $this->formatJson($response);
        }
    }

    /**
     * Formats response data in JSON format.
     * @param Response $response
     */
    protected function formatJson($response)
    {
        $response->getHeaders()->set('Content-Type', 'application/json; charset=UTF-8');
        $response->content = Json::encode($response->data, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Formats response data in JSONP format.
     *
     * @param Response $response
     * @throws ResponseException
     */
    protected function formatJsonp($response)
    {
        $response->getHeaders()->set('Content-Type', 'application/javascript; charset=UTF-8');
        if (is_array($response->data) && isset($response->data['data'], $response->data['callback'])) {
            $response->content = sprintf('%s(%s);', $response->data['callback'], Json::encode($response->data['data']));
        } else {
            $response->content = '';
            throw new ResponseException("The 'jsonp' response requires that the data be an array consisting of both 'data' and 'callback' elements.");
        }
    }
} 