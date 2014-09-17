<?php

namespace rock\authclient\clients\OAuth2;


use OAuth\Common\Consumer\Credentials;
use OAuth\ServiceFactory;
use rock\authclient\ClientInterface;
use rock\authclient\storages\Session;
use rock\base\ComponentsInterface;
use rock\base\ComponentsTrait;
use rock\exception\ErrorHandler;
use rock\helpers\Json;
use rock\helpers\JsonException;
use rock\request\Request;
use rock\Rock;
use rock\url\Url;

class Facebook implements ComponentsInterface, ClientInterface
{
    use ComponentsTrait;

    public $clientId;

    public $clientSecret;

    public $redirectUrl;

    public $apiUrl = '/me';
    public $scopes = ['email'];

    /** @var \OAuth\OAuth2\Service\Facebook  */
    protected $service;

    public function init()
    {
        if (isset($this->service)) {
            return;
        }
        $serviceFactory = new ServiceFactory();
        // Session storage
        $storage = new Session();
        /** @var Url $urlBuilder */
        $urlBuilder = Rock::factory($this->redirectUrl, Url::className());

        // Setup the credentials for the requests
        $credentials = new Credentials(
            $this->clientId,
            $this->clientSecret,
            $urlBuilder->getAbsoluteUrl()
        );
        $this->service = $serviceFactory->createService('facebook', $credentials, $storage, $this->scopes);
    }

    /**
     * @return \OAuth\OAuth2\Service\Facebook
     */
    public function getService()
    {
        return $this->service;
    }


    /**
     * {@inheritdoc}
     */
    public function getAuthorizationUrl()
    {
        return $this->service->getAuthorizationUri()->getAbsoluteUri();
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributes($code = null)
    {
        if (!isset($code)) {
            $code = Request::get('code');
        }

        if (empty($code)) {
            return [];
        }
        // This was a callback request from google, get the token
        $this->service->requestAccessToken($code);
        // Send a request with it
        try {
            return Json::decode($this->service->request($this->apiUrl));
        } catch (JsonException $e) {
            Rock::error(ErrorHandler::convertExceptionToString($e));
        }

        return [];
    }

}