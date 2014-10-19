<?php
namespace rock\di;

use rock\exception\BaseException;

/**
 * Exception "Exception"
 *
 * @package rock\template
 */
class Exception extends BaseException
{

    const ARGS_NOT_ARRAY = 'Object configuration must be an array containing a "class" element.';
    const INVALID_CONFIG = 'Configuration must be an array or \Closure.';

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