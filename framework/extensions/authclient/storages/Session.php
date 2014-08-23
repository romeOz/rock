<?php

namespace rock\authclient\storages;

use OAuth\Common\Storage\TokenStorageInterface;
use OAuth\Common\Token\TokenInterface;
use rock\authclient\Exception;
use rock\Rock;

/**
 * Stores a token in a PHP session.
 */
class Session implements TokenStorageInterface
{
    /**
     * @var string
     */
    protected $sessionVariableName;

    /**
     * @var string
     */
    protected $stateVariableName;

    protected $session;

    /**
     * @param bool $startSession Whether or not to start the session upon construction.
     * @param string $sessionVariableName the variable name to use within the _SESSION superglobal
     * @param string $stateVariableName
     */
    public function __construct(
        $startSession = true,
        $sessionVariableName = 'lusitanian_oauth_token',
        $stateVariableName = 'lusitanian_oauth_state'
    ) {

        $this->session = Rock::$app->session;
        $this->sessionVariableName = $sessionVariableName;
        $this->stateVariableName = $stateVariableName;
    }

    /**
     * {@inheritDoc}
     */
    public function retrieveAccessToken($service)
    {
        if ($this->hasAccessToken($service)) {
            return unserialize($this->session->get([$this->sessionVariableName, $service]));
        }

        throw new Exception(Exception::ERROR, 'Token not found in session, are you sure you stored it?');
    }

    /**
     * {@inheritDoc}
     */
    public function storeAccessToken($service, TokenInterface $token)
    {
        $serializedToken = serialize($token);

        if ($this->session->has($this->sessionVariableName)
            && is_array($this->session->get($this->sessionVariableName))
        ) {
            $this->session->add([$this->sessionVariableName, $service], $serializedToken);
        } else {
            $this->session->add($this->sessionVariableName, [$service => $serializedToken]);
        }

        // allow chaining
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function hasAccessToken($service)
    {
        return $this->session->has($this->sessionVariableName) && $this->session->has([$this->sessionVariableName, $service]);
    }

    /**
     * {@inheritDoc}
     */
    public function clearToken($service)
    {
        if (array_key_exists($service, $this->session->get($this->sessionVariableName))) {
            $this->session->remove([$this->sessionVariableName, $service]);
        }

        // allow chaining
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function clearAllTokens()
    {
        $this->session->remove($this->sessionVariableName);

        // allow chaining
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function storeAuthorizationState($service, $state)
    {
        if ($this->session->has($this->stateVariableName)
            && is_array($this->session->get($this->stateVariableName))
        ) {
            $this->session->add([$this->stateVariableName, $service], $state);
        } else {
            $this->session->add($this->stateVariableName, [$service => $state]);
        }

        // allow chaining
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function hasAuthorizationState($service)
    {
        return $this->session->has($this->stateVariableName) && $this->session->has([$this->stateVariableName, $service]);
    }

    /**
     * {@inheritDoc}
     */
    public function retrieveAuthorizationState($service)
    {
        if ($this->hasAuthorizationState($service)) {
            return $this->session->get([$this->stateVariableName, $service]);
        }

        throw new Exception(Exception::ERROR, 'State not found in session, are you sure you stored it?');
    }

    /**
     * {@inheritDoc}
     */
    public function clearAuthorizationState($service)
    {

        if (array_key_exists($service, $this->session->get($this->stateVariableName))) {
            $this->session->remove([$this->stateVariableName, $service]);
        }

        // allow chaining
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function clearAllAuthorizationStates()
    {
        $this->session->remove($this->stateVariableName);
        // allow chaining
        return $this;
    }
} 