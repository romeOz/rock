<?php

namespace rock\authclient;


interface ClientInterface 
{

    public function getService();

    /**
     * @return string
     */
    public function getAuthorizationUrl();

    /**
     * @param string $code
     * @return array
     */
    public function getAttributes($code = null);
} 