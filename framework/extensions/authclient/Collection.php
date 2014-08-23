<?php

namespace rock\authclient;


use rock\base\ComponentsInterface;
use rock\base\ComponentsTrait;
use rock\Rock;

/**
 * Collection is a storage for all auth clients in the application.
 *
 * Example application configuration:
 *
 * ```php
 * 'components' => [
 *  'authClientCollection' => [
 *      'class' => 'rock\authclient\Collection',
 *      'clients' => [
 *          'google' => [
 *              'class' => 'rock\authclient\clients\GoogleOAuth2',
 *              'clientId' => 'google_client_id',
 *              'clientSecret' => 'google_client_secret',
 *              'redirectUrl' => '/social/google/'
 *          ],
 *          'facebook' => [
 *              'class' => 'rock\authclient\clients\Facebook',
 *              'clientId' => 'facebook_client_id',
 *              'clientSecret' => 'facebook_client_secret',
 *          ],
 *      ],
 *  ]
 * ...
 * ]
 * ```
 *
 * @property ClientInterface[] $clients List of auth clients. This property is read-only.
 *
 */
class Collection implements ComponentsInterface
{
    use ComponentsTrait;


    /**
     * @var array list of Auth clients with their configuration in format: 'clientId' => [...]
     */
    private $_clients = [];

    /**
     * @param array $clients list of auth clients
     */
    public function setClients(array $clients)
    {
        $this->_clients = $clients;
    }

    /**
     * @return ClientInterface[] list of auth clients.
     */
    public function getClients()
    {
        $clients = [];
        foreach ($this->_clients as $id => $client) {
            $clients[$id] = $this->getClient($id);
        }

        return $clients;
    }

    /**
     * @param string $client service id.
     * @return ClientInterface auth client instance.
     * @throws Exception on non existing client request.
     */
    public function getClient($client)
    {
        if (!array_key_exists($client, $this->_clients)) {
            throw new Exception(Exception::CRITICAL, "Unknown auth client '{$client}'.");
        }
        if (!is_object($this->_clients[$client])) {
            $this->_clients[$client] = $this->createClient($this->_clients[$client]);
        }

        return $this->_clients[$client];
    }

    /**
     * Checks if client exists in the hub.
     * @param string $id client id.
     * @return boolean whether client exist.
     */
    public function hasClient($id)
    {
        return array_key_exists($id, $this->_clients);
    }

    /**
     * Creates auth client instance from its array configuration.
     *
     * @param array $config auth client instance configuration.
     * @return ClientInterface auth client instance.
     */
    protected function createClient($config)
    {
        return Rock::factory($config);
    }
} 