<?php

namespace rock\request;


use rock\exception\BaseException;

class Exception extends BaseException
{
    /**
     * Constructor
     *
     * @param int|string      $level       - type of exception
     * @param string|int|null $msg         - message
     * @param array           $placeholders - array replace
     * @param \Exception      $handler     - handler
     */
    public function __construct($level = self::ERROR, $msg = null, array $placeholders = [], \Exception $handler = null){
        parent::__construct($level, $msg, $placeholders, $handler);
    }
} 