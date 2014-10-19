<?php
namespace rock\captcha;

use rock\exception\BaseException;

/**
 * Exception "Exception"
 *
 * @package rock\captcha
 */
class Exception extends BaseException
{
    const CAPTCHA_ERR = 0;


    /**
     * Constructor
     *
     * @param int|string  $level       - type of exception
     * @param string|null $msg         - message
     * @param array       $placeholders - array replace
     * @param \Exception  $handler     - handler
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