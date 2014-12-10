<?php

namespace rock\authclient\clients\OAuth2;


use OAuth\Common\Consumer\Credentials;
use OAuth\ServiceFactory;
use rock\authclient\ClientInterface;
use rock\authclient\storages\Session;
use rock\base\ComponentsInterface;
use rock\base\ComponentsTrait;
use rock\exception\BaseException;
use rock\helpers\Json;
use rock\helpers\JsonException;
use rock\request\Request;
use rock\Rock;
use rock\url\Url;

class Google implements ComponentsInterface, ClientInterface
{
    use ComponentsTrait;

    public $clientId;

    public $clientSecret;

    public $redirectUrl;

    public $apiUrl = 'https://www.googleapis.com/oauth2/v1/userinfo';
    public $scopes = ['userinfo_email', 'userinfo_profile'];

    /** @var \OAuth\OAuth2\Service\Google  */
    protected $service;

    public function init()
    {
        if (isset($this->service)) {
            return;
        }
        $serviceFactory = new ServiceFactory();
        // Session storage
        $storage = new Session(false);
        $urlBuilder = Url::set($this->redirectUrl);

        // Setup the credentials for the requests
        $credentials = new Credentials(
            $this->clientId,
            $this->clientSecret,
            $urlBuilder->getAbsoluteUrl()
        );

        // Instantiate the Google service using the credentials, http client and storage mechanism for the token
        $this->service = $serviceFactory->createService('google', $credentials, $storage, $this->scopes);
    }

    /**
     * @return \OAuth\OAuth2\Service\Google
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
            Rock::error($e->getMessage(), [], BaseException::getTracesByException($e));
        }

        return [];
    }
} 