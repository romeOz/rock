<?php
namespace rock\url;

use rock\exception\BaseException;

/**
 * Exception "Exception"
 *
 * @package rock\url
 */
class Exception extends BaseException
{
    const DEFAULT_RULE = 0;
    const MANAGER_ERR  = 1;
    const CONFIG_EMPTY = 2;


    /**
     * Constructor
     *
     * @param int|string      $level       - type of exception
     * @param string|null     $msg         - message
     * @param array           $placeholders - array replace
     * @param \Exception|null $handler     - handler
     * @internal param int|null|string $code - code
     */
    public function __construct(
        $level = self::ERROR,
        $msg = null,
        array $placeholders = [],
        \Exception $handler = null
    ){
        parent::__construct($level, $msg, $placeholders, $handler);
    }
}