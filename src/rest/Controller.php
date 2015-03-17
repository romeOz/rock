<?php
namespace rock\rest;

use rock\response\Response;
use rock\filters\ContentNegotiatorFilter;
use rock\filters\VerbFilter;
use rock\filters\RateLimiter;
use rock\di\Container;

/**
 * Controller is the base class for RESTful API controller classes.
 *
 * Controller implements the following steps in a RESTful API request handling cycle:
 *
 * 1. Resolving response format ({@see \rock\filters\ContentNegotiatorFilter});
 * 2. Validating request method ({@see \rock\rest\Controller::verbs()});
 * 4. Rate limiting ({@see \rock\filters\RateLimiter});
 * 5. Formatting response data ({@see \rock\rest\Controller::serializeData()}).
 */
class Controller extends \rock\core\Controller
{
    /**
     * @var string|array the configuration for creating the serializer that formats the response data.
     */
    public $serializer = 'rock\rest\Serializer';

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return parent::behaviors() + [
            'contentNegotiator' => [
                'class' => ContentNegotiatorFilter::className(),
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,
                    'application/xml' => Response::FORMAT_XML,
                ],
                'response' => $this->response
            ],
            'verbFilter' => [
                'class' => VerbFilter::className(),
                'actions' => $this->verbs(),
                'response' => $this->response
            ],
            'rateLimiter' => [
                'class' => RateLimiter::className(),
                'response' => $this->response
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function afterAction($action, $result)
    {
        $result = parent::afterAction($action, $result);
        return $this->serializeData($result);
    }

    /**
     * Declares the allowed HTTP verbs.
     * Please refer to {@see \rock\filters\VerbFilter::$actions} on how to declare the allowed verbs.
     * @return array the allowed HTTP verbs.
     */
    protected function verbs()
    {
        return [];
    }

    /**
     * Serializes the specified data.
     * The default implementation will create a serializer based on the configuration given by {@see \rock\rest\Serializer}.
     * It then uses the serializer to serialize the given data.
     * @param mixed $data the data to be serialized
     * @return mixed the serialized data.
     */
    protected function serializeData($data)
    {
        return Container::load(['class' => $this->serializer, 'response' => $this->response])->serialize($data);
    }
}