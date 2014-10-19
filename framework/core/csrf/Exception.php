<?php
namespace rock\csrf;

use rock\exception\BaseException;

class Exception extends BaseException
{
    /**
     * Constructor
     *
     * @param int|string  $level       type of exception
     * @param string|null $msg         message
     * @param array       $placeholders placeholders for replacing
     * @param \Exception  $handler     handler
     */
    public function __construct($level = self::ERROR, $msg = null, array $placeholders = [], \Exception $handler = null){
        parent::__construct($level, $msg, $placeholders, $handler);
    }
}