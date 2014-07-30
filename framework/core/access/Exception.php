<?php

namespace rock\access;


use rock\exception\BaseException;

class Exception extends BaseException
{
    /**
     * Constructor
     *
     * @param int|string      $level       - type of exception
     * @param string|null     $msg         - message
     * @param array           $dataReplace - array replace
     * @param \Exception|null $handler     - handler
     */
    public function __construct($level = self::ERROR, $msg = null, array $dataReplace = [], \Exception $handler = null){
        parent::__construct($level, $msg, $dataReplace, $handler);
    }
} 