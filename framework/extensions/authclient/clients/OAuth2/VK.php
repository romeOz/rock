<?php

namespace rock\authclient\clients\OAuth2;


use OAuth\Common\Consumer\Credentials;
use OAuth\Common\Http\Client\StreamClient;
use rock\authclient\ClientInterface;
use rock\authclient\services\Vkontakte;
use rock\authclient\storages\Session;
use rock\base\ComponentsInterface;
use rock\base\ComponentsTrait;
use rock\exception\BaseException;
use rock\helpers\Json;
use rock\helpers\JsonException;
use rock\request\Request;
use rock\Rock;
use rock\url\Url;

class VK implements ComponentsInterface, ClientInterface
{
    use ComponentsTrait;

    public $clientId;

    public $clientSecret;

    public $redirectUrl;

    /**
     * @see https://vk.com/dev/api_requests
     * @var string
     */
    public $apiUrl = 'https://api.vk.com/method/users.get?fields=uid,first_name,last_name,nickname,sex,bdate';

    /** @var Vkontakte  */
    protected $service;
    public $scopes = ['email'];

    public function init()
    {
        if (isset($this->service)) {
            return;
        }
        //$serviceFactory = new ServiceFactory();
        // Session storage
        $storage = new Session();
        $urlBuilder = Url::set($this->redirectUrl);

        // Setup the credentials for the requests
        $credentials = new Credentials(
            $this->clientId,
            $this->clientSecret,
            $urlBuilder->getAbsoluteUrl()
        );

        $this->service = new Vkontakte($credentials, new StreamClient(), $storage, $this->scopes);
    }

    /**
     * @return Vkontakte
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
        $extraAttributes = $this->service->requestAccessToken($code)->getExtraParams();
        // Send a request with it
        try {
            return array_merge(Json::decode($this->service->request($this->apiUrl)), ['extra' => $extraAttributes]);
        } catch (JsonException $e) {
            Rock::error($e->getMessage(), [], BaseException::getTracesByException($e));
        }

        return [];
    }
}