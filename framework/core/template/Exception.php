<?php

namespace rock\template;


use rock\exception\BaseException;

class Exception extends BaseException
{
    const UNKNOWN_SNIPPET = 'Unknown snippet: {name}';
    const UNKNOWN_FILTER = 'Unknown filter: {name}';
    const UNKNOWN_PARAM_FILTER = 'Unknown param filter: {filter}';

    /**
     * Constructor
     *
     * @param int|string  $level       - type of exception
     * @param string|null $msg         - message
     * @param array       $placeholders - array replace
     * @param \Exception  $handler     - handler
     */
    public function __construct($level = self::ERROR, $msg = null, array $placeholders = [], \Exception $handler = null){
        parent::__construct($level, $msg, $placeholders, $handler);
    }

} 